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
        return VenueOrder::calculateCreditScore($this->id, $datetime);
    }
}
