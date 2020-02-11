<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\VenueRole;

class Role extends BaseController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return json(VenueRole::where(['school_id' => app()->user->schoolid, 'status' => VenueRole::STATUS_NORMAL])->select());
    }
}
