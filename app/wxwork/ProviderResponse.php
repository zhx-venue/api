<?php

namespace app\wxwork;

/**
 * SAAS应用回调事件处理回复类
 */
class ProviderResponse extends Response 
{
    public function deal() {
        $method = strval($this->data->InfoType);

        trace("[push $method msg]-".json_encode($this->data));
        if(method_exists($this, $method))   return $this->$method();

        return null;
    }

    // 使用注册推广包注册完成回调事件
    public function register_corp() { }
}
