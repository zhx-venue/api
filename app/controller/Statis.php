<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\exception\AccessException;
use app\model\User;
use app\model\VenueOrder;

class Statis extends BaseController
{
    public function info()
    {
        $this->_checkAccess();

        
        $stime = input('get.stime', strtotime(date('Ymd 0:0:0')), 'intval');
        $etime = input('get.etime', strtotime(date('Ymd 23:59:59')), 'intval');

        $expand = input('get.expand', '', 'strval');
        $expand = is_array($expand) ? $expand : array_keys(array_flip(array_filter(explode(',', strval($expand)))));

        return json(VenueOrder::statisInfo($stime, $etime, $expand));
    }

    /**
     * 权限检测
     */
    private function _checkAccess()
    {
        if (app()->user->type != User::TYPE_USER) {
            throw new AccessException('无权限查看该内容');
        }
    }
}
