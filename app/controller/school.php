<?php
declare (strict_types = 1);

namespace app\controller;

use think\Request;
use app\BaseController;

class school extends BaseController
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
}
