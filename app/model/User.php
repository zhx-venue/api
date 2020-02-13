<?php
declare (strict_types = 1);

namespace app\model;
use thans\jwt\facade\JWTAuth;

/**
 * 登录用户
 */
class User
{
    const TYPE_USER = 1;
    const TYPE_VISITOR = 2;

    // 用户登录数据
    protected $payload = null;

    public function isGuest()
    {
        return $this->payload === null;
    }

    public function __get($name)
    {
        return $this->payload[$name] ?? null;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * 生成口令
     */
    public static function generateToken($user, $schoolId=null)
    {
        if (method_exists($user, 'generateToken')) {
            return $user->generateToken($schoolId);
        }

        throw new \Exception('user instance invalid');
    }
}
