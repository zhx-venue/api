<?php

namespace app\wxwork;

use app\model\CorpAgent;

class OAuth
{
    /**
     * 读取并缓存企业服务实例
     * @param string $corpid 企业的CorpID
     * @param string $suiteId 第三方应用套件ID
     * @param string $suiteSecret 第三方应用套件密钥
     */
    public static function getCorpInstance($corpid)
    {
        static $_instance = [];
        if (!empty($corpid) && !isset($_instance[$corpid])) {
            try {
                $agentInfo = CorpAgent::find($corpid);
                if (!empty($agentInfo)) {
                    $_instance[$corpid] = new Service($corpid, $agentInfo->permanent_code);
                }
            } catch (\Exception $e) { }
        }

        return isset($_instance[$corpid]) ? $_instance[$corpid] : null;
    }

    /**
     * 读取并缓存第三方应用服务实例
     */
    public static function getServiceInstance()
    {
        static $_instance = null;
        if (!$_instance) {
            try {
                $_instance = new Service();
            } catch (\Exception $e) { $_instance = null; }
        }

        return $_instance;
    }

    /**
     * 读取并缓存服务商服务实例
     */
    public static function getProviderInstance()
    {
        static $_instance = null;
        if (!$_instance) {
            try {
                $_instance = new ServiceProvider();
            } catch (\Exception $e) { $_instance = null; }
        }

        return $_instance;
    }

    /**
     * 构建企业授权链接
     * @param $appid 企业的CorpID
     * @param $redirectUrl 授权后重定向的回调链接地址，请使用urlencode对链接进行处理
     * @param $state 重定向后会带上state参数
     */
    public static function generateAuthorizeCorpUrl($appid, $redirectUrl, $state='')
    {
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($redirectUrl).'&response_type=code&scope=snsapi_base&state='.$state.'#wechat_redirect';
    }

    /**
     * 构建企业应用授权链接
     * @param $appid 企业的CorpID
     * @param $redirectUrl 授权后重定向的回调链接地址，请使用urlencode对链接进行处理
     * @param $scope 应用授权作用域。
                    snsapi_base：静默授权，可获取成员的的基础信息（UserId与DeviceId）；
                    snsapi_userinfo：静默授权，可获取成员的详细信息，但不包含手机、邮箱；
                    snsapi_privateinfo：手动授权，可获取成员的详细信息，包含手机、邮箱
     * @param $agentid 企业应用的id，当scope是snsapi_userinfo或snsapi_privateinfo时，该参数必填
     * @param $state 重定向后会带上state参数
     */
    public static function generateAuthorizeCorpAgentUrl($appid, $redirectUrl, $scope, $agentid, $state='')
    {
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($redirectUrl).'&response_type=code&scope='.$scope.'&agentid='.$agentid.'&state='.$state.'#wechat_redirect';
    }

    /**
     * 构建第三方应用授权链接
     * @param $appid 第三方应用id（即ww或wx开头的suite_id）。注意域名需要设置为第三方应用的可信域名
     * @param $redirectUrl 授权后重定向的回调链接地址，请使用urlencode对链接进行处理
     * @param $scope 应用授权作用域。
                    snsapi_base：静默授权，可获取成员的的基础信息（UserId与DeviceId）；
                    snsapi_userinfo：静默授权，可获取成员的详细信息，但不包含手机、邮箱；
                    snsapi_privateinfo：手动授权，可获取成员的详细信息，包含手机、邮箱
     * @param $state 重定向后会带上state参数
     */
    public static function generateAuthorizeSuiteUrl($appid, $redirectUrl, $scope, $state='')
    {
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($redirectUrl).'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
    }

    /**
     * 构建第三方单点登陆授权链接
     * @param $appid 服务商的CorpID
     * @param $redirectUrl 授权后重定向的回调链接地址，请使用urlencode对链接进行处理，所在域名需要与授权完成回调域名一致
     * @param $state 用于企业或服务商自行校验session，防止跨域攻击
     * @param $usertype 支持登录的类型。admin代表管理员登录（使用微信扫码）,member代表成员登录（使用企业微信扫码），默认为admin
     */
    public static function generateThirdConnectUrl($appid, $redirectUrl, $state='web_login@gyoss9', $usertype='admin')
    {
        return 'https://open.work.weixin.qq.com/wwopen/sso/3rd_qrConnect?appid='.$appid.'&redirect_uri='.urlencode($redirectUrl).'&state='.$state.'&usertype='.$usertype;
    }
}
