<?php
declare(strict_types=1);

namespace app\controller\wxwork;

use app\BaseController;
use app\wxwork\MsgCrypt;
use app\procedure\CorpResponse;

use app\wxwork\OAuth;
use app\model\Corp as MCorp;
use app\model\CorpAgent;
use app\model\VenueSchool;
use app\helper\StringHelper;

class Corp extends BaseController
{
    // 重置中间件
    protected function _middleware() {}
    
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

    /**
     * 企业微信jsskd签名
     */
    public function get_jsapi_sign()
    {
        $url = input('get.url');
        $corpid = input('get.corpid');
        if (empty($url) || empty($corpid))  return $this->jsonErr('无效的参数');
        if (strpos($url, '#')) return $this->jsonErr('url中的#号无法识别');

        $corpInfo = MCorp::find($corpid);
        if (empty($corpInfo))   return $this->jsonErr('无效的CORPID');
        $corpAgent = CorpAgent::find($corpInfo->corpid);
        if (empty($corpAgent))  return $this->jsonErr('企业号未安装德育管理应用，请联系管理员');
        $schoolInfo = VenueSchool::where('corpid', $corpInfo->corpid)->find();
        if (empty($schoolInfo)) return $this->jsonErr('企业号未正确安装德育管理应用，请联系管理员重新安装');

        try {
            $now = time();
            $nonceStr = StringHelper::getRandomStr(16);
            $corpApi = OAuth::getCorpInstance($corpInfo->corpid);
            $jsApiTicket = $corpApi->JsApiTicketGet();
            $signature = $corpApi->JsApiSignatureGet($jsApiTicket, $nonceStr, $now, $url);
            $data['appId'] = $corpid;
            $data['timestamp'] = $now;
            $data['nonceStr'] = $nonceStr;
            $data['signature'] = $signature;
            return $this->jsonOk($data);
        } catch (\Exception $e) {
            return $this->jsonErr('获取签名失败!'.$e->getMessage());
        }
    }
}
