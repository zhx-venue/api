<?php

namespace app\wxwork;

use shophy\wxwork\common\WXBizMsgCrypt;

class MsgCrypt extends WXBizMsgCrypt
{
    /**
	 * 构造函数
	 * @param $token string 公众平台上，开发者设置的token
	 * @param $encodingAesKey string 公众平台上，开发者设置的EncodingAESKey
	 * @param $Corpid string 公众平台的Corpid
	 */
	public function __construct($Corpid)
	{
		$token = config('wxwork.suite_token') ?? '';
        $encodingAesKey = config('wxwork.suite_encoding_aes_key') ?? '';
		parent::__construct($token, $encodingAesKey, $Corpid);
	}
}
