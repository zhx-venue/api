<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;
use thans\jwt\facade\JWTAuth;

/**
 * @mixin think\Model
 */
class VenueVisitor extends BaseModel
{
    /**
     * 格式化字段的查询条件
     */
    protected function formatFilter($field, $value) 
    {
        switch ($field) {
            case 'name' : { return ['like', '%'.$value.'%']; }
            case 'mobile' : { return ['=', strval($value)]; }
        }

        return null;
    }

    /**
     * 添加访客
     */
    public function addItem($data)
    {
        $find = self::where(['openid' => $data['openid']])->find();
        if ($find)  throw new \Exception('成员已存在');

        self::create($data);
    }

    /**
     * 读取预约过学校的访客
     * @param string $schoolId 学校id
     */
    public function listBySchool($schoolId)
    {
        return self::alias('vv')
            ->field('vv.*')
            ->join(VenueOrder::getTable().' vo', 'vo.visitor_id=vv.id')
            ->where('vo.school_id', $schoolId ?? app()->user->schoolid)
            ->select();
    }

    public function generateToken($tokenData=[])
    {
        $payload = array_merge([
            'id' => $this->id, 
            'type' => User::TYPE_VISITOR, 
            'name' => $this->getAttr('name'),
            'openid' => $this->openid, 
            'avatar' => $this->avatar,
            'gender' => $this->gender
        ], $tokenData);

        return [
            'info' => [
                'id' => $this->id,
                'name' => $this->getAttr('name'),
                'avatar' => $this->avatar,
                'gender' => $this->gender
            ],
            'token' => JWTAuth::builder($payload)
        ];
    }

    /**
     * 读取访客在学校的状态
     */
    public function getBanStatus()
    {
        if (app()->user->type == User::TYPE_USER) {
            $banInfo = VenueVisitorBan::where(['school_id' => app()->user->schoolid, 'visitor_id' => $this->id])->order('created_at', 'desc')->find();
        }

        return isset($banInfo) ? $banInfo->status : 1;
    }

    /**
     * 统计游客预约次数
     */
    public function getOrderCounts()
    {
        return VenueOrder::where(['visitor_id' => $this->id, 'status' => self::STATUS_NORMAL])->where('process', 'not in', [VenueOrder::PROCESS_CANCEL, VenueOrder::PROCESS_REVOKED])->count();
    }

    /**
     * 统计游客履行次数
     */
    public function getPerformCounts()
    {
        // 有签到的预约
        return VenueOrderHistory::where(['visitor_id' => $this->id, 'optype' => VenueOrderHistory::OPTYPE_SIGNING])->count();
    }

    /**
     * 统计游客信用分
     */
    public function getCreditScore($datetime=null)
    {
        $init = 100; // 月初始分
        $revoke = -6; // 退订扣分
        $overdue = -10; // 逾期扣分
        $complete = 2; // 正常完成得分

        // 给定时间，当月份所应有的天数
        $time = $datetime ?? time();
        $mdays = date('t', $time);

        // 查询游客所有预约记录
        $now = time();
        VenueOrder::where('visitor_id', $this->id)
        ->where('process', '<>', VenueOrder::PROCESS_CHECKING)
        ->where('status', VenueOrder::STATUS_NORMAL)
        ->where('odate', '>=', strtotime(date('Y-m-1 00:00:00', $time)))
        ->where('odate', '<=', strtotime(date('Y-m-'.$mdays.' 23:59:59',$time)))
        ->chunk(100, function ($records) use (&$init, &$revoke, &$overdue, &$complete) {
            foreach ($records as $record) {
                switch ($record->process) {
                    case VenueOrder::PROCESS_SIGNING: {
                        // 过了结束时间还未入场，已逾期
                        $orderTime = parse_ordertime($record->odate, $record->open_time);
                        $now > $orderTime[1] && $init -= $overdue;
                        break;
                    }
                    case VenueOrder::PROCESS_REVOKED: { // 退订
                        $init -= $revoke;
                        break;
                    }
                    case VenueOrder::PROCESS_SIGNOUTED: { // 已签退，完成预定过程
                        $init += $complete;
                        break;
                    }
                    case VenueOrder::PROCESS_CANCEL: // 已取消预约
                    case VenueOrder::PROCESS_REFUSED: // 已拒绝预约
                    case VenueOrder::PROCESS_SIGNOUTING: { // 已签到待签退
                        break;
                    }
                }
            }
        });

        return $init;
    }
}
