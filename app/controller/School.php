<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\VenueRole;

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
        if (!checkAuth(VenueRole::MD_SCHOOL))   return $this->jsonErr('无权限进行该操作');

        $model = new $this->modelClass;
        $query = $model->parseFilter();
        $query->where(['status' => $this->modelClass::STATUS_NORMAL]);

        return json($model->listItem($query));
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        if (!checkAuth(VenueRole::MD_SCHOOL))   return $this->jsonErr('无权限进行该操作');

        $query = $this->modelClass::where(['id' => $id]);
        try {
            return json((new $this->modelClass)->getItem($query));
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
    }

    /**
     * 通过场地类型搜索学校
     */
    public function list_by_venue()
    {
        if (!checkAuth(VenueRole::MD_SCHOOL))   return $this->jsonErr('无权限进行该操作');

        $type = input('get.type', 0, 'intval');
        $page = input('get.page', 1, 'intval');
        $psize = input('get.psize', $this->modelClass::SIZE_PER_PAGE, 'intval');
        return json((new $this->modelClass)->listByVenue($type, $page, $psize));
    }

    /**
     * 设置学校设置信息
     */
    public function set_config()
    {
        if (!checkAuth(VenueRole::MD_SCHOOL, 1))   return $this->jsonErr('无权限进行该操作');

        if (($this->modelClass)::updateConf(input('post.'))) {
            return $this->jsonOk();
        }

        return $this->jsonErr('更新失败');
    }
}
