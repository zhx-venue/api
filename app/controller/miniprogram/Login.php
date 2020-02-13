<?php
declare(strict_types=1);

namespace app\controller\miniprogram;

use app\BaseController;
use app\model\User;
use app\model\VenueVisitor;
use app\validate\VenueVisitor as VVenueVisitor;
use app\miniprogram\Api as MiniApi;

class Login extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }

    /**
     * 小程序用户登录
     */
    public function token($code)
    {
        try {
            $api = new MiniApi();
            $res = $api->getSessionByCode($code);
            if (!$res)  return $this->jsonErr('['.$api->errCode.']'.$api->errMsg);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        $openid = $res['openid'] ?? '';
        $sessionKey = $res['session_key'] ?? '';
        if (empty($openid)) return $this->jsonErr('获取用户信息失败');

        $visitor = VenueVisitor::where('openid', $res['openid'])->find();
        if (empty($visitor))    return $this->jsonErr('无访客信息', 401, ['openid' => $openid, 'session_key' => $sessionKey]);

        $userToken = User::generateToken($visitor);
        isset($res['session_key']) && $userToken['session_key'] = isset($res['session_key']);
        return $this->jsonOk($userToken);
    }

    /**
     * 小程序登录测试接口
     */
    public function test()
    {
        $mobile = input('get.mobile');
        $visitor = VenueVisitor::where('mobile', $mobile)->find();
        if (empty($visitor))    return $this->jsonErr('无访客信息', 401);

        $userToken = User::generateToken($visitor);
        isset($res['session_key']) && $userToken['session_key'] = isset($res['session_key']);
        return $this->jsonOk($userToken);
    }
}
