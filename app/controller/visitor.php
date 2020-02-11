<?php
namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\VenueVisitor;

class Visitor extends BaseController
{
    public $modelClass = 'app\model\VenueVisitor';

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return json((new VenueVisitor)->listItem());
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        $query = $this->modelClass::where(['id' => $id]);
        return json((new $this->modelClass)->getItem($query));
    }
}
