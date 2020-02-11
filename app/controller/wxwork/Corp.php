<?php
declare(strict_types=1);

namespace app\controller\wxwork;

use app\BaseController;
use app\wxwork\MsgCrypt;
use app\procedure\CorpResponse;

class Corp extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }
    
    // 应用 数据回调 事件处理入口
    public function handle()
    {
        $corpId = input('get.corpid');
        $sReqNonce = input('get.nonce');
        $sReqTimestamp = input('get.timestamp');
        $sReqMsgSignature = input('get.msg_signature');
        if(empty($sReqNonce) || empty($sReqTimestamp) || empty($sReqMsgSignature))    exit;

        $wxcpt = new MsgCrypt($corpId);
        $sEchoStr = input('get.echostr');
        if(! empty($sEchoStr)) {
            $sReplyEchoStr = '';
            $errCode = $wxcpt->VerifyURL($sReqMsgSignature, $sReqTimestamp, $sReqNonce, rawurldecode($sEchoStr), $sReplyEchoStr);
            if ($errCode == 0) {
                trace('['.$corpId.']'.' VerifyURL success : '.$sReplyEchoStr, 'info');
                exit($sReplyEchoStr);
            } else {
                trace('['.$corpId.']'.' VerifyURL error : '.$errCode, 'error');
                exit;
            }
        }

        $xmlData = '';
        $errCode = $wxcpt->DecryptMsg($sReqMsgSignature, $sReqTimestamp, $sReqNonce, input('post.'), $xmlData);
        if($errCode !== 0) {
            trace('['.$corpId.']'.' DecryptMsg error : '.$errCode, 'error');
            exit;
        }

        trace('['.$corpId.']'.' xml data : '.$xmlData, 'info');
        $xmlData = simplexml_load_string($xmlData, "SimpleXMLElement", LIBXML_NOCDATA);
        $xmlData->oriCorpId = $corpId;

        $replyMsg = (new CorpResponse($xmlData))->deal();
        if(empty($replyMsg))    exit;

        $sEncryptMsg = ''; // xml格式的密文
        $errCode = $wxcpt->EncryptMsg($replyMsg, $sReqTimestamp, $sReqNonce, $sEncryptMsg);
        if ($errCode == 0) {
            exit($sEncryptMsg);
        } else {
            trace('['.$corpId.']'.' EncryptMsg error : '.$errCode, 'error');
        }
    }
}
