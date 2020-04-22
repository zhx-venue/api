<?php
declare(strict_types=1);

namespace app\controller\wxwork;

use app\BaseController;
use app\wxwork\MsgCrypt;
use app\wxwork\Service as WxService;
use app\procedure\CreateAuth;
use app\procedure\InstallForm;
use app\procedure\ServiceResponse;
use shophy\wxwork\structs\SessionInfo;
use shophy\wxwork\structs\SetSessionInfoReq;

class Service extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }

    /**
     * 安装酷柠教育应用
     */
    public function install()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        try {
            $wxServiceApi = new WxService();

            // 获取预授权码
            $preAuthCode = $wxServiceApi->GetPreAuthCode();

            $sessionInfo = new SessionInfo();
            $sessionInfo->appid = []; // 允许进行授权的应用id，如1、2、3， 不填或者填空数组都表示允许授权套件内所有应用（仅旧的多应用套件可传此参数，新开发者可忽略）
            env('app_debug') && $sessionInfo->auth_type = 1; // 授权类型：0 正式授权， 1 测试授权。 默认值为0。

            $sessionInfoReq = new SetSessionInfoReq();
            $sessionInfoReq->session_info = $sessionInfo;
            $sessionInfoReq->pre_auth_code = $preAuthCode;

            // 设置授权配置
            $wxServiceApi->SetSessionInfo($sessionInfoReq);

            // 授权回调也用该入口，通过 $_GET['auth_code'] 判断是否回调
            $authCode = input('get.auth_code');
            if(empty($authCode)) {
                $state = 'zhihuixiao'; // state可填a-zA-Z0-9的参数值（不超过128个字节），用于第三方自行校验session，防止跨域攻击。
                $suiteId = config('wxwork.suiteid') ?? '';
                $redirectUrl = urlencode($this->request->url(true));
                return redirect("https://open.work.weixin.qq.com/3rdapp/install?suite_id=$suiteId&pre_auth_code=$preAuthCode&redirect_uri=$redirectUrl&state=$state");
            } else {
                $installForm = new InstallForm();
                $installForm->permanentInfo = $wxServiceApi->GetPermanentCode($authCode);
                // $installForm->newsArticle = [
                //     ['title' => '邀请同事加入企业流程', 'description' => '', 'url' => $app->params['FRONT_DOMAIN'].'/qywx/static/page/invite.html', 'picurl' => 'https://img.kuningkeji.com/invite.png', 'btntxt' => '详情']
                // ];
                $installForm->record();

                //处理加密信息
                $authData = json_encode([
                    'corpid' => $installForm->permanentInfo->auth_corp_info->corpid,
                    'name' => $installForm->permanentInfo->auth_user_info->name,
                    'avatar' => $installForm->permanentInfo->auth_user_info->avatar,
                    'userid' => $installForm->permanentInfo->auth_user_info->userid,
                ]);
                $cipherText = base64_encode(json_encode($authData, JSON_UNESCAPED_UNICODE));
                redirect(config('wxwork.front_domain') ?? '/index/installed'.'?cipher_text='.urlencode($cipherText))->send();

                // 初始化学校主体信息
                CreateAuth::deal($installForm->permanentInfo);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 应用 指令回调事件处理
     */
    public function handle()
    {
        $sReqNonce = input('get.nonce');
        $sReqTimestamp = input('get.timestamp');
        $sReqMsgSignature = input('get.msg_signature');
        if(empty($sReqNonce) || empty($sReqTimestamp) || empty($sReqMsgSignature))    exit;

        $sEchoStr = input('get.echostr');
        $corpId = empty($sEchoStr) ? (config('wxwork.suiteid') ?? '') : (config('wxwork.corpid') ?? '');
        $wxcpt = new MsgCrypt($corpId);
        if(! empty($sEchoStr)) {
            $sReplyEchoStr = '';
            $errCode = $wxcpt->VerifyURL($sReqMsgSignature, $sReqTimestamp, $sReqNonce, rawurldecode($sEchoStr), $sReplyEchoStr);
            if ($errCode == 0) {
                trace('['.$corpId.']'.' VerifyURL success : '.$sReplyEchoStr, 'info');
                exit($sReplyEchoStr);
            } else {
                trace('['.$corpId.']'.' VerifyURL error : '.$errCode, 'error');
                return;
            }
        }

        $xmlData = '';
        $errCode = $wxcpt->DecryptMsg($sReqMsgSignature, $sReqTimestamp, $sReqNonce, $this->request->getInput(), $xmlData);
        if($errCode !== 0) {
            trace('['.$corpId.']'.' DecryptMsg error : '.$errCode.' : '.$this->request->getInput(), 'error');
            return;
        }

        trace('['.$corpId.']'.' xml data : '.$xmlData, 'info');
        $xmlData = simplexml_load_string($xmlData, "SimpleXMLElement", LIBXML_NOCDATA);
        $xmlData->oriCorpId = $corpId;

        (new ServiceResponse($xmlData))->deal();
    }
}
