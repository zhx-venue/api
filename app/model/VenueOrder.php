<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

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
        $venueFacility = VenueFacility::find($data['facility_id']);
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
            'odate' => date('Ymd 0:0:0', intval($data['order_time']['stime'])), 
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

        Db::startTrans();
        try {
            // 更新场地记录
            $venueInfo->updated_by = app()->user->id ?? 0;
            $venueInfo->save();

            // 删除不需要的设备
            (isset($delFacility) && !empty($delFacility)) && VenueFacility::update(['status' => self::STATUS_DELETE], ['id' => array_keys($delFacility)]);
            // 更新保留的设备
            if (isset($updateFacility)) {
                foreach ($updateFacility as $_upFacility) {
                    $_upFacility->updated_by = app()->user->id ?? 0;
                    $_upFacility->save();
                }
            }
            // 添加新的设备
            if (isset($addFacility)) {
                foreach ($addFacility as $_addFacility) {
                    $_addFacility->status = self::STATUS_NORMAL;
                    $_addFacility->school_id = app()->user->schoolid ?? 0;
                    $_addFacility->created_by = app()->user->id ?? 0;
                    $_addFacility->updated_by = app()->user->id ?? 0;

                    $find = VenueFacility::where(['school_id' => app()->user->schoolid, 'title' => $_addFacility->title])->find();
                    $find ? VenueFacility::update($_addFacility->getData(), ['id' => $find->id]) : $_addFacility->save();
                }
            }

            // 删除不需要的场地图片
            isset($delImages) && VenueImage::destroy($delImages);
            // 添加新的场地图片
            (isset($addImages) && empty($addImages)) || (new VenueImage)->saveAll(array_values($addImages));

            Db::commit(); // 提交事务
        } catch (\Exception $e) {
             Db::rollback(); // 回滚事务
            trace('添加场地失败:'.$e->getMessage(), 'error');
            throw new \Exception('添加场地失败');
        }
    }

    public function getVisitor()
    {
        return VenueVisitor::find($this->visitor_id);
    }

    public function getSchool()
    {
        return VenueSchool::find($this->school_id);
    }
}
