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
            $api = new MiniApi(input('get.appid'));
            $api->getSessionByCode($code);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        $openid = $api->rspJson['openid'] ?? '';
        $sessionKey = $api->rspJson['session_key'] ?? '';
        if (empty($openid)) return $this->jsonErr('获取用户信息失败');

        $visitor = VenueVisitor::where('openid', $openid)->find();
        if (empty($visitor))    return $this->jsonErr('无访客信息', 401, ['openid' => $openid, 'session_key' => $sessionKey]);

        $userToken = User::generateToken($visitor);
        empty($sessionKey) || $userToken['session_key'] = $sessionKey;
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

    public function decryptData()
	{
        $iv = input('post.iv', '', 'strval');
        if (empty($iv)) return $this->jsonErr('iv不能为空');
        $openid = input('post.openid', '', 'strval');
        if (empty($openid)) return $this->jsonErr('openid不能为空');
        $encryptedData = input('post.encryptedData', '', 'strval');
        if (empty($encryptedData)) return $this->jsonErr('加密数据不能为空');

		$data = null;
		try {
            $api = new MiniApi(input('post.appid'));
            $api->decryptData($encryptedData, $iv, $openid, $data);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
        
        return $this->jsonOk($data);
	}
}
