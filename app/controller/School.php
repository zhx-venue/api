<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;

class School extends BaseController
{
    public $modelClass = 'app\model\VenueSchool';

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $model = new $this->modelClass;
        $query = $model->parseFilter();
        if ($query) {
            $query = $query->where(['status' => $this->modelClass::STATUS_NORMAL]);
        } else {
            $query = $this->modelClass::where(['status' => $this->modelClass::STATUS_NORMAL]);
        }

        return json($model->listItem($query));
    }

    /**
     * 通过场地类型搜索学校
     */
    public function list_by_venue()
    {
        $type = input('get.type', 0, 'intval');
        $page = input('get.page', 1, 'intval');
        $psize = input('get.psize', $this->modelClass::SIZE_PER_PAGE, 'intval');
        return json((new $this->modelClass)->listByVenue($type, $page, $psize));
    }
}