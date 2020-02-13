<?php

namespace app\wxwork;

use app\model\CorpAgent;
use app\procedure\InstallForm;

/**
 * SAAS应用回调事件处理回复类
 */
class ServiceResponse extends Response 
{
    public function deal() {
        $method = strval($this->data->InfoType);
        $method == 'change_contact' && $method = strval($this->data->ChangeType);

        trace("[push $method msg]-".json_encode($this->data));
        if(method_exists($this, $method)) {
            $this->sendReply();
            return $this->$method();
        }

        return null;
    }

    // 推送suite_ticket
    public function suite_ticket() 
    {
        cache($this->data->oriCorpId.'Ticket', strval($this->data->SuiteTicket));
    }

    // 授权成功通知,从企业微信应用市场发起授权时，企业微信后台会推送授权成功通知
    public function create_auth() {
        $installForm = new InstallForm();
        $installForm->permanentInfo = (new Service())->GetPermanentCode($this->data->AuthCode);
        isset($this->data->wx_message) && $installForm->newsArticle = $this->data->wx_message;
        $installForm->record();

        $this->data->permanentInfo = $installForm->permanentInfo;
    }
    
    // 变更授权通知
    public function change_auth() {
        $agentInfo = CorpAgent::find($this->data->AuthCorpId);
        if ($agentInfo) {
            $installForm = new InstallForm();
            $installForm->permanentInfo = (new Service())->GetAuthInfo($this->data->AuthCorpId, $agentInfo->permanent_code);
            $installForm->record();
        }

        Contacts::listDepartments($this->data->AuthCorpId, true);
    }
    
    // 取消授权通知
    public function cancel_auth() {
        InstallForm::uninstall($this->data->AuthCorpId, $this->data->SuiteId);
    }
    
    // 新增成员事件
    public function create_user() { }
    
    // 更新成员事件
    public function update_user() { }
    
    // 删除成员事件
    public function delete_user() { }
    
    // 新增部门事件
    public function create_party() { }
    
    // 更新部门事件
    public function update_party() { }
    
    // 删除部门事件
    public function delete_party() { }
    
    // 标签成员变更事件
    public function update_tag() { }

    protected function sendReply()
    {
        set_time_limit(0);
        ignore_user_abort();
        response('success')->send();
    }
}
