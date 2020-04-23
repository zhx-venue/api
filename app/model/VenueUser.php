<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;
use think\facade\Db;
use thans\jwt\facade\JWTAuth;

/**
 * @mixin think\Model
 */
class VenueUser extends BaseModel
{
    const TYPE_ADMIN = 0;
    const TYPE_CUSTOMER = 1;

    public function generateToken($tokenData=[])
    {
        $ip = ip2long(app()->request->ip());
        $now = time();

        // 添加用户操作记录
        $userLog = new VenueUserLog();
        $userLog->opip = $ip;
        $userLog->optype = 0;
        $userLog->opdata = $this->type;
        $userLog->created_by = $userLog->updated_by = $this->id;
        $userLog->save();

        // 更新用户登录统计
        self::update([
            'last_login_ip' => $ip, 
            'last_login_time' => $now, 
            'login_count' => Db::raw('login_count+1'), 
            'check_count' => 0,/* 登录成功自动清除错误统计 */
            'ban_expire' => 0/* 登录成功则自动解禁 */
        ], ['id' => $this->id]);

        $info = array_merge([
            'id' => $this->id, 
            'name' => $this->getAttr('name'),
            'avatar' => $this->avatar,
        ], empty($this->openuserid) ? ['corpid' => $this->corpid, 'userid' => $this->userid] : ['openuserid' => $this->openuserid]);
        return [
            'info' => $info,
            'token' => JWTAuth::builder(array_merge($info, ['type' => User::TYPE_USER], $tokenData))
        ];
    }
}
