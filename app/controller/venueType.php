<?php
declare(strict_types=1);

namespace app\controller;

use think\Request;
use app\BaseController;
use app\model\VenueSchoolType;
use app\validate\VenueType as VVenueType;
use think\exception\ValidateException;

class Venuetype extends BaseController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return json(VenueSchoolType::list(input('get.')));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
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
