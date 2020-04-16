<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\User;
use app\model\VenueRole;
use app\model\VenueOrder;
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
        if (!checkAuth(VenueRole::MD_VISITOR))   return $this->jsonErr('无权限进行该操作');

        $model = new $this->modelClass;
        $query = $model->parseFilter();
        $query->where(['status' => $this->modelClass::STATUS_NORMAL]);
        if (app()->user->type == User::TYPE_USER) {
            // 企业微信管理员只能查看预约过本校场地的访客
            //$query->rightJoin(VenueOrder::getTable().' vo', 'vo.visitor_id='.($this->modelClass)::getTable().'.id')
            //    ->where(['vo.school_id' => app()->user->schoolid]);
        }

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
        if (!checkAuth(VenueRole::MD_VISITOR))   return $this->jsonErr('无权限进行该操作');

        $query = $this->modelClass::where(['id' => $id]);
        try {
            return json((new $this->modelClass)->getItem($query));
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
    }
}
