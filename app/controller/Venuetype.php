<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\VenueSchoolType;
use app\validate\VenueType as VVenueType;
use think\exception\ValidateException;
use app\model\VenueRole;

class Venuetype extends BaseController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if (!checkAuth(VenueRole::MD_VENUETYPE))   throw new AccessException('无权限进行该操作');
        return json(VenueSchoolType::list(input('get.')));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        if (!checkAuth(VenueRole::MD_VENUETYPE, 1))   throw new AccessException('无权限进行该操作');

        $data = input('post.');
        try {
            validate(VVenueType::class)->scene('add')->batch(true)->check($data);

            VenueSchoolType::addType($data);
        } catch (ValidateException $e) {
            return $this->jsonErr($e->getError());
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        return $this->jsonOk();
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!checkAuth(VenueRole::MD_VENUETYPE, 1))   throw new AccessException('无权限进行该操作');
        
        $data = ['id' => $id];
        try {
            validate(VVenueType::class)->scene('del')->batch(true)->check($data);

            VenueSchoolType::delType($id);
        } catch (ValidateException $e) {
            return $this->jsonErr($e->getError());
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        return $this->jsonOk();
    }
}
