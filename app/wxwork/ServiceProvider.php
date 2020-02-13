<?php

namespace app\wxwork;

use shophy\wxwork\ServiceProviderAPI;
use shophy\wxwork\common\Utils;
use shophy\wxwork\common\HttpUtils;

class ServiceProvider extends ServiceProviderAPI
{
    /**
     * 调用SetAgentScope/SetContactSyncSuccess 两个接口可以不用传corpid/provider_secret
     */
    public function __construct()
    {
        $corpid = config('wxwork.corpid') ?? '';
        $providerSecret = config('wxwork.provider_secret') ?? '';
        parent::__construct($corpid, $providerSecret);
    }

    protected function RefreshProviderAccessToken($bflush=false)
    {
        Utils::checkNotEmptyStr($this->corpid, "corpid");
        Utils::checkNotEmptyStr($this->provider_secret, "provider_secret");

        // 尝试从缓存读取,这里是服务商接口的accesstoken
        $this->provider_access_token = $bflush ? '' : cache($this->corpid);
        if( ! Utils::notEmptyStr($this->provider_access_token)) {
            $args = array(
                "corpid" => $this->corpid, 
                "provider_secret" => $this->provider_secret
            ); 
            $url = HttpUtils::MakeUrl("/cgi-bin/service/get_provider_token");
            $this->_HttpPostParseToJson($url, $args, false);
            $this->_CheckErrCode();
    
            // 写入缓存
            $this->provider_access_token = $this->rspJson["provider_access_token"];
            cache($this->corpid, $this->provider_access_token, $this->rspJson['expires_in']);
        }
    }
}
