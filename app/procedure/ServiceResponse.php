<?php

namespace app\procedure;

use app\model\VenueUser;
use app\model\VenueMember;

/**
 * SAAS应用回调事件处理回复类
 */
class ServiceResponse extends \app\wxwork\ServiceResponse
{
    // 授权成功通知,从企业微信应用市场发起授权时，企业微信后台会推送授权成功通知
    public function create_auth()
    {
        // $this->data->wx_message = [
        //     ['title' => '邀请同事加入企业流程', 'description' => '', 'url' => $app->params['FRONT_DOMAIN'].'/qywx/static/page/invite.html', 'picurl' => 'https://img.kuningkeji.com/invite.png', 'btntxt' => '详情']
        // ];
        parent::create_auth();

        // 初始化学校主体信息
        CreateAuth::deal($this->data->permanentInfo);
    }

    // 变更授权通知
    public function change_auth()
    {
        parent::change_auth();
    }

    // 取消授权通知
    public function cancel_auth()
    {
        parent::cancel_auth();

    }

    // 新增成员事件
    public function create_user()
    {
        parent::create_user();
    }

    // 更新成员事件
    public function update_user()
    {
        parent::update_user();
        if (isset($this->data->Name) || isset($this->data->Avatar)) {
            $userInfo = VenueUser::where(['corpid' => $this->data->AuthCorpId, 'userid' => $this->data->UserID])->find();
            if ($userInfo) {
                isset($this->data->Name) && $userInfo->name = $this->data->Name;
                isset($this->data->Avatar) && $userInfo->avatar = $this->data->Avatar;
                $userInfo->save();

                $membInfo = VenueMember::where('user_id', $userInfo->id)->find();
                if ($membInfo) {
                    isset($this->data->Name) && $membInfo->name = $this->data->Name;
                    isset($this->data->Avatar) && $membInfo->avatar = $this->data->Avatar;
                    $membInfo->save();
                }
            }
            
        }
    }

    // 删除成员事件
    public function delete_user()
    {
        parent::delete_user();

        $userInfo = VenueUser::where(['corpid' => $this->data->AuthCorpId, 'userid' => $this->data->UserID])->find();
        $membInfo = $userInfo ? VenueMember::where('user_id', $userInfo->id)->find() : null;
        $membInfo && $membInfo->delete();
    }

    // 新增部门事件
    public function create_party()
    {
        parent::create_party();
    }

    // 更新部门事件
    public function update_party()
    {
        parent::update_party();
    }

    // 删除部门事件
    public function delete_party()
    {
        parent::delete_party();
    }

    private static function parseExtattr($extAttr)
    {
        $attrs = [];
        if(isset($extAttr->Item)) {
            foreach($extAttr->Item as $item) {
                $attrs[strval($item->Name)] = strval($item->Value);
            }
        }

        return json_encode($attrs);
    }
}
