<?php

namespace app\wxwork;

/** 
 * 安装应用企业事件回调处理回复类
 */
class CorpResponse extends Response 
{
    public function deal() {
        $method = strval($this->data->MsgType);
        $method == 'event' && $method .= '_'.strval($this->data->Event);
        $method == 'event_change_contact' && $method = 'event_'.strval($this->data->ChangeType);

        trace("[push $method msg]-".json_encode($this->data), 'info');
        if(method_exists($this, $method))   return $this->$method();

        return null;
    }

    // 文本消息
    public function text() {  }
    // 图片消息
    public function image() {  }
    // 语音消息
    public function voice() {  }
    // 视频消息
    public function video() {  }
    // 链接消息
    public function link() {  }
    // 位置消息
    public function location() {  }
    // 订阅
    public function event_subscribe() {  }
    // 取消订阅
    public function event_unsubscribe() {  }
    // 新增成员事件
    public function event_create_user() {  }
    // 更新成员事件
    public function event_update_user() {  }
    // 删除成员事件
    public function event_delete_user() {  }
    // 新增部门事件
    public function event_create_party() {  }
    // 更新部门事件
    public function event_update_party() {  }
    // 删除部门事件
    public function event_delete_party() {  }
    // 标签成员变更事件
    public function event_update_tag() {  }
    // 进入应用
    public function event_enter_agent() {  }
    // 上报地理位置
    public function event_location() {  }
    // 异步任务完成事件推送
    public function event_batch_job_result() {  }
    // 点击菜单拉取消息的事件推送
    public function event_click() {  }
    // 点击菜单跳转链接的事件推送
    public function event_view() {  }
    // 扫码推事件的事件推送
    public function event_scancode_push() {  }
    // 扫码推事件且弹出“消息接收中”提示框的事件推送
    public function event_scancode_waitmsg() {  }
    // 弹出系统拍照发图的事件推送
    public function event_pic_sysphoto() {  }
    // 弹出拍照或者相册发图的事件推送
    public function event_pic_photo_or_album() {  }
    // 弹出微信相册发图器的事件推送
    public function event_pic_weixin() {  }
    // 弹出地理位置选择器的事件推送
    public function event_location_select() {  }
}
