<?php
// 应用公共文件

use app\model\User;
use app\model\VenueRole;

/**
 * 权限检测
 * @param int $module 模块
 * @param int $position 模块操作
 * @return true or false
 */
function checkAuth($module, $position=null)
{
    $auths = [];
    if (app()->user->isGuest()) {
        $auths = VenueRole::getGuestAuth();
    } elseif (app()->user->type == User::TYPE_VISITOR) {
        $auths = VenueRole::getVisitorAuth();
    } else {
        $auths = VenueRole::getUserAuth();
    }

    return VenueRole::checkAuth($module, $position, $auths);
}