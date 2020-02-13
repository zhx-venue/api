<?php

namespace app\wxwork;

use shophy\wxwork\ServiceCorpAPI;
use shophy\wxwork\common\Utils;
use shophy\wxwork\common\HttpUtils;

class Service extends ServiceCorpAPI 
{
    public function __construct(
        $authCorpId=null, 
        $permanentCode=null)
    {
        $suiteId = config('wxwork.suiteid') ?? '';
        $suiteSecret = config('wxwork.suite_secret') ?? '';
        $suiteTicket = cache($suiteId.'Ticket');

        parent::__construct($suiteId, $suiteSecret, $suiteTicket, $authCorpId, $permanentCode);
    }

    public function getSuiteid()
    {
        return $this->suite_id;
    }

    public function getAuthCorpid()
    {
        return $this->authCorpId;
    }

    /**
     * @brief RefreshAccessToken : override CorpAPI的函数，使用三方服务商的get_corp_token
     *
     * @return : string
     */
    public function RefreshAccessToken($bflush=false)
    {
        Utils::checkNotEmptyStr($this->authCorpId, "auth_corpid");
        Utils::checkNotEmptyStr($this->permanentCode, "permanent_code");

        // 尝试从缓存读取,corpid 作为key
        $this->accessToken = $bflush ? '' : cache($this->authCorpId.'-'.$this->permanentCode);
        if( ! Utils::notEmptyStr($this->accessToken)) {
            $args = array(
                "auth_corpid" => $this->authCorpId, 
                "permanent_code" => $this->permanentCode
            ); 
            $url = HttpUtils::MakeUrl("/cgi-bin/service/get_corp_token?suite_access_token=SUITE_ACCESS_TOKEN");
            $this->_HttpPostParseToJson($url, $args, false);
            $this->_CheckErrCode();
    
            // 写入缓存
            $this->accessToken = $this->rspJson["access_token"];
            cache($this->authCorpId.'-'.$this->permanentCode, $this->accessToken, $this->rspJson['expires_in']);
        }
    }

    protected function RefreshSuiteAccessToken($bflush=false)
    {
        Utils::checkNotEmptyStr($this->suite_id, "suite_id");
        Utils::checkNotEmptyStr($this->suite_secret, "suite_secret");
        Utils::checkNotEmptyStr($this->suite_ticket, "suite_ticket");

        // 尝试从缓存读取,suite_id 作为key
        $this->suiteAccessToken = $bflush ? '' : cache($this->suite_id);
        if( ! Utils::notEmptyStr($this->suiteAccessToken)) {
            $args = array(
                "suite_id" => $this->suite_id, 
                "suite_secret" => $this->suite_secret,
                "suite_ticket" => $this->suite_ticket,
            ); 
            $url = HttpUtils::MakeUrl("/cgi-bin/service/get_suite_token");
            $this->_HttpPostParseToJson($url, $args, false);
            $this->_CheckErrCode();
    
            // 写入缓存
            $this->suiteAccessToken= $this->rspJson["suite_access_token"];
            cache($this->suite_id, $this->suiteAccessToken, $this->rspJson['expires_in']);
        }
    }

    /**
     * @brief JsApiTicketGet : 获取jsapi_ticket
     *
     * @link https://work.weixin.qq.com/api/doc#10029/获取jsapi_ticket
     *
     * @return : string ticket
     */
    public function JsApiTicketGet()
    {
        if ( ! Utils::notEmptyStr($this->jsApiTicket)) { 
            $this->RefreshJsApiTicket();
        } 
        return $this->jsApiTicket;
    }

    /** 
     * 刷新jsapiTiket
     */
    protected function RefreshJsApiTicket($bflush=false) 
    {
        Utils::checkNotEmptyStr($this->authCorpId, "auth_corpid");
        Utils::checkNotEmptyStr($this->suite_secret, "suite_secret");
        
        // 尝试从缓存读取,corpid 作为key
        $this->jsApiTicket = $bflush ? '' : cache($this->authCorpId.'-'.$this->suite_secret.'jsapiTicket');
        if( ! Utils::notEmptyStr($this->jsApiTicket)) {
            self::_HttpCall(self::GET_JSAPI_TICKET, 'GET', null); 
    
            // 写入缓存
            $this->jsApiTicket = $this->rspJson["ticket"];
            cache($this->authCorpId.'-'.$this->suite_secret.'jsapiTicket', $this->jsApiTicket, $this->rspJson['expires_in']);
        }
    }
}
