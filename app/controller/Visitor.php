<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\User;
use app\model\VenueRole;
use app\model\VenueOrder;
use app\model\VenueVisitor;
use app\model\VenueVisitorBan;

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
            $query->where('id', 'IN', function ($query) {
                $query->table(VenueOrder::getTable())->where('school_id', app()->user->schoolid)->field('visitor_id');
            });
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

        // 访客只能查看自己的信息
        if (app()->user->type == User::TYPE_VISITOR && app()->user->id != $id)  return $this->jsonErr('仅能查看自己的信息');

        $query = $this->modelClass::where(['id' => $id]);
        try {
            return json((new $this->modelClass)->getItem($query));
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
    }

    /**
     * 禁用访客预约
     */
    public function ban($id)
    {
        if (!checkAuth(VenueRole::MD_VISITOR, 1))   return $this->jsonErr('无权限进行该操作');

        $visitorInfo = $this->modelClass::where(['id' => $id])->find();
        if(empty($visitorInfo)) return $this->jsonErr('无效的参数');

        try {
            VenueVisitorBan::create([
                'school_id' => app()->user->schoolid,
                'visitor_id' => $id,
                'status' => 0
            ]);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
        
        return $this->jsonOk();
    }

    /**
     * 解禁访客预约
     */
    public function allow($id)
    {
        if (!checkAuth(VenueRole::MD_VISITOR, 1))   return $this->jsonErr('无权限进行该操作');

        $visitorInfo = $this->modelClass::where(['id' => $id])->find();
        if(empty($visitorInfo)) return $this->jsonErr('无效的参数');

        try {
            VenueVisitorBan::create([
                'school_id' => app()->user->schoolid,
                'visitor_id' => $id,
                'status' => 1
            ]);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
        
        return $this->jsonOk();
    }
}
