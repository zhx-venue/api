<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;
use app\helper\XDeode;

/**
 * @mixin think\Model
 */
class VenueOrder extends BaseModel
{
    const PROCESS_CANCEL = -1; // 已取消
    const PROCESS_CHECKING = 0; // 待审核
    const PROCESS_SIGNING = 1; // 待签到
    const PROCESS_SIGNOUTING = 2; // 待签退
    const PROCESS_REVOKED = 3; // 已退订
    const PROCESS_SIGNOUTED = 4; // 已签退

    /**
     * 格式化字段的查询条件
     */
    protected function formatFilter($field, $value) 
    {
        switch ($field) {
            case 'date': {
                return ['odate', '=', strtotime(date('Ymd 0:0:0', intval($value)))];
            }
            case 'venue_id': 
            case 'school_id': { return ['=', intval($value)]; }
            case 'process': {
                if (is_numeric($value)) {
                    return ['=', intval($value)];
                } else {
                    return ['in', strval($value)];
                }
            }
        }

        return null;
    }

    /**
     * 添加记录
     */
    public function addItem($data)
    {
        // 场地数据校验
        $venueFacility = VenueFacility::where(['id' => $data['facility_id'], 'status' => self::STATUS_NORMAL])->find();
        if (empty($venueFacility)) throw new \Exception('无效的场地设备');

        // 检查场地是否开放
        if (intval($venueFacility->option) & 1)    throw new \Exception('该场地已关闭');

        // 检查是否超过单次预约时间限制
        if ($venueFacility->limit_ordertime > 0) {
            $orderTime = $data['order_time']['etime'] - $data['order_time']['stime'];
            if ($orderTime > ($venueFacility->limit_ordertime * 1800)) {
                throw new \Exception('该场地单次预约时长不能超过'.round($venueFacility->limit_ordertime / 2, 1).'小时');
            }
        }

        // 检查预约时间是否可以在开放时间内
        $orderTime = Venue::parseOpentime([['stime' => $data['order_time']['stime'], 'etime' => $data['order_time']['etime']]]);
        if ($venueFacility->open_time != (intval($venueFacility->open_time) | $orderTime)) {
            throw new \Exception('预约时间已不在该场地开放时间内');
        }

        // 检查预约时间是否已经被预约
        $orderedTime = 0;
        $orderedTimes = self::where(['venue_id' => $venueFacility->venue_id, 'facility_id' => $venueFacility->id])->where('process', 'not in', [self::PROCESS_CANCEL, self::PROCESS_REVOKED])->column('open_time');
        foreach ($orderedTimes as $value) {
            $orderedTime |= intval($value);
        }
        if ($orderTime & $orderedTime)  throw new \Exception('该时间段已经被预约了');

        // 添加预约记录
        $orderInfo = self::create([
            'school_id' => $venueFacility->school_id, 
            'visitor_id' => app()->user->id, 
            'venue_id' => $venueFacility->venue_id, 
            'facility_id' => $venueFacility->id, 
            'odate' => strtotime(date('Ymd 0:0:0', intval($data['order_time']['stime']))), 
            'open_time' => $orderTime, 
            'people_counts' => $data['people_counts'], 
            'process' => self::PROCESS_CHECKING, 
            'created_by' => app()->user->id, 
            'updated_by' => app()->user->id, 
        ]);
    }

    /**
     * 编辑记录
     */
    public function updateItem($id, $data)
    {
        $orderInfo = self::find($id);
        if (empty($orderInfo))  throw new \Exception('场地不存在');

        // 权限判断
        if (app()->user->type == User::TYPE_VISITOR) {
            if (app()->user->id != $orderInfo->visitor_id)  throw new \Exception('无权限更新该记录');
        } else {
            if (app()->user->schoolid != $orderInfo->school_id) throw new \Exception('无权限更新该记录');

            // 获取用户权限信息
            $auths = VenueRole::getUserAuth();
            if (!($auths['pos'] & 3))   throw new \Exception('无权限更新该记录');
        }

        $orderInfo->process = $data['process'];
        $orderInfo->updated_by = app()->user->id;
        if (!($orderInfo->save()))   throw new \Exception('更新失败');
    }

    public function getVisitor()
    {
        return VenueVisitor::find($this->visitor_id);
    }

    public function getSchool()
    {
        return VenueSchool::find($this->school_id);
    }

    /**
     * 获取预约记录唯一识别码
     */
    public function getUniquecode()
    {
        return (new XDeode)->encode($this->id);
    }

    /**
     * 解析预约记录唯一识别码
     */
    public function parseUniquecode($code)
    {
        return (new XDeode)->decode($code);
    }

    /**
     * 格式化场地开放时间
     */
    public function getOpentime()
    {
        $bitCounts = 0;
        $timeRange = [];
        for ($i = 0; $i < 48; ++$i) {
            $bopen = $this->open_time & (1<<$i);
            $bopen && $bitCounts++;
            ($bopen && !(count($timeRange)%2)) && $timeRange[] = date('Y年m月d日 H:i', $this->odate+$i*1800);
            (!$bopen && (count($timeRange)%2)) && $timeRange[] = date('Y年m月d日 H:i', $this->odate+$i*1800);
        }

        $ranges = [];
        $timeRange = array_chunk($timeRange, 2);
        foreach ($timeRange as $_range) {
            $ranges[] = $_range[0].'~'.$_range[1];
        }

        return ['counts' => round($bitCounts/2, 1), 'ranges' => $ranges];
    }

    /**
     * 统计预约数据
     * @param int $stime 开始统计时间
     * @param int $etime 结束统计时间
     * @param array $expand 要统计的数据
     */
    public static function statisInfo($stime, $etime, $expand)
    {
        $info = [];
        foreach ($expand as $_exField) {
            $method = '_statis'.ucfirst($_exField);
            if (method_exists(static::class, $method)) {
                $info[$_exField] = null;
            }
        }

        $counts = 0;
        self::where('school_id', app()->user->schoolid)
        ->where('process', '<>', self::PROCESS_CANCEL)
        ->where('status', self::STATUS_NORMAL)
        ->where('odate', '>=', strtotime(date('Ymd 0:0:0', intval($stime))))
        ->where('odate', '<=', strtotime(date('Ymd 0:0:0', intval($etime))))
        ->chunk(100, function ($records) use (&$counts, &$info) {
            foreach ($records as $record) {
                ++$counts;

                foreach ($info as $_exField => $value) {
                    $method = '_statis'.ucfirst($_exField);
                    $record->$method($info[$_exField]);
                }
            }
        });

        $info['counts'] = $counts;
        return $info;
    }

    private function _statisHours(&$value)
    {
        $orderTimes = [];
        for ($i = 0; $i < 48; ++$i) {
            $bopen = $this->open_time & (1<<$i);
            $bopen && $orderTimes[intval($i/2)] = 1;
        }

        $value = $value ?? [];
        foreach ($orderTimes as $_hour => $_val) {
            if (isset($value[$_hour])) {
                ++$value[$_hour];
            } else {
                $value[$_hour] = 1;
            }
        }
    }

    private function _statisVenuetype(&$value) 
    {
        $value = $value ?? [];
        if (isset($value[$this->venue_id])) {
            ++$value[$this->venue_id]['counts'];
        } else {
            $typeTitle = VenueType::alias('vt')
                ->join(Venue::getTable().' v', 'v.type=vt.id')
                ->where(['v.id' => $this->venue_id])->value('vt.title');
            empty($typeTitle) || $value[$this->venue_id] = ['id' => $this->venue_id, 'title' => $typeTitle, 'counts' => 1];
        }
    }
}
