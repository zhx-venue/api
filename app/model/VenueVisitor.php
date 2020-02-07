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

    public function generateToken($schoolId=null)
    {
        $payload = [
            'id' => $this->id, 
            'type' => User::TYPE_VISITOR, 
            'name' => $this->getAttr('name'),
            'openid' => $this->openid, 
            'avatar' => $this->avatar,
            'gender' => $this->gender
        ];

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
}
