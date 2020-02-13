<?php

namespace app\wxwork;

use shophy\wxwork\CorpAPI;
use shophy\wxwork\common\Utils;
use shophy\wxwork\common\HttpUtils;
use shophy\wxwork\exception\ParameterException;

class Corp extends CorpAPI
{
    protected $jsApiTicket = null; // string

    /**
     * @brief __construct : 构造函数，
     * @note 企业进行自定义开发调用, 请传参 corpid + secret, 不用关心accesstoken，本类会自动获取并刷新
     */
    public function __construct($corpId=null, $secret=null)
    {
        parent::__construct($corpId, $secret);
    }

    /** 
     * 刷新accesstoekn
     */
    protected function RefreshAccessToken($bflush=false)
    {
        if (!Utils::notEmptyStr($this->corpId) || !Utils::notEmptyStr($this->secret))
            throw new ParameterException("invalid corpid or secret");

        // 尝试从缓存读取,corpid 作为key
        $this->accessToken = $bflush ? '' : cache($this->corpId.'-'.$this->secret);
        if( ! Utils::notEmptyStr($this->accessToken)) {
            $url = HttpUtils::MakeUrl(
                "/cgi-bin/gettoken?corpid={$this->corpId}&corpsecret={$this->secret}");
            $this->_HttpGetParseToJson($url, false);
            $this->_CheckErrCode();
    
            // 写入缓存
            $this->accessToken = $this->rspJson["access_token"];
            cache($this->corpId.'-'.$this->secret, $this->accessToken, $this->rspJson['expires_in']);
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
        // 尝试从缓存读取,corpid 作为key
        $this->jsApiTicket = $bflush ? '' : cache($this->corpId.'-'.$this->secret.'jsapiTicket');
        if( ! Utils::notEmptyStr($this->jsApiTicket)) {
            self::_HttpCall(self::GET_JSAPI_TICKET, 'GET', null); 
    
            // 写入缓存
            $this->jsApiTicket = $this->rspJson["ticket"];
            cache($this->corpId.'-'.$this->secret.'jsapiTicket', $this->jsApiTicket, $this->rspJson['expires_in']);
        }
    }
}
