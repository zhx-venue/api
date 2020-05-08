<?php

namespace app\campus;

use think\facade\Cache;
use shophy\campus\Campus;
use shophy\campus\models\RefreshAccessTokenRequest;

/**
 * 智慧校园 accesstoken缓存
 */
class AccessToken
{
    // 刷新token的有效期时间,单位秒
    const REFRESH_TIME = 900;

    /**
     * 缓存accesstoken并返回key
     * 缓存不支持读取过期时间，使用缓存数据保存过期时间
     */
    public static function cache($accesstoken, $expire)
    {
        $key = uniqid();
        $expire = intval($expire);
        $data = [
            'expire' => time() + $expire,
            'token' => strval($accesstoken),
        ];

        Cache::set($key, json_encode($data), $expire);
        return $key;
    }

    /**
     * 检查缓存有效期是否需要刷新accesstoken
     * @return true or false
     */
    public static function check($cachekey)
    {
        $data = Cache::get($cachekey, null);
        if (empty($data))   return false;

        $data = json_decode($data, true);
        if ($data === false)    return false;
        if ($data['expire'] < (time() + self::REFRESH_TIME)) {
            try {
                $request = new RefreshAccessTokenRequest();
                $request->deserialize(['CurrentAccessToken' => $data['token']]);
    
                $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '');
                $response = $campus->RefreshAccessToken($request);
            } catch (\Exception $e) {
                trace('[check accesstoken] - '.$e->getMessage(), 'error');
                return false;
            }

            Cache::set($cachekey, json_encode([
                'expire' => time() + $response->ExpireIn,
                'token' => strval($response->AccessToken),
            ]), $response->ExpireIn);
        }

        return true;
    }
}
