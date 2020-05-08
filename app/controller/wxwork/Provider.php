<?php
declare(strict_types=1);

namespace app\controller\wxwork;

use app\BaseController;
use app\wxwork\ServiceProvider;

/**
 * 企业微信服务商平台回调处理 控制器
 */
class Provider extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }

    /**
     * 单点登录 获取登录用户信息
     * auth_code	是	oauth2.0授权企业微信管理员登录产生的code，最长为512字节。只能使用一次，5分钟未被使用自动过期
     * 场景：
     *  管理员从企业微信管理端单点登录第三方
     *  管理员或成员在第三方网站发起登录授权
     * usertype    1.创建者 2.内部系统管理员 3.外部系统管理员 4.分级管理员 5.成员
     */
    //服务商后台登录入口
    public function loginInfo()
    {
        $authCode = input('get.auth_code');
        if(empty($authCode))    return;
        try {
            redirect((config('wxwork.front_domain') ?? '/index/installed').'?auth_code='.$authCode)->send();
            //return json((new ServiceProvider())->GetLoginInfo($authCode));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
