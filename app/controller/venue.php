<?php
namespace app\controller;

use think\Request;
use app\BaseController;
use think\exception\ValidateException;

class Venue extends BaseController
{
    public $modelClass = 'app\model\Venue';
    public $validateClass = 'app\validate\Venue';

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
            $query = $query->where(['school_id' => app()->user->schoolid, 'status' => $this->modelClass::STATUS_NORMAL]);
        } else {
            $query = $this->modelClass::where(['school_id' => app()->user->schoolid, 'status' => $this->modelClass::STATUS_NORMAL]);
        }

        return json($model->listItem($query));
    }

    /**
     * 访客搜索场地
     */
    public function search()
    {
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = input('post.');
        try {
            validate($this->validateClass)->scene('add')->batch(true)->check($data);

            (new $this->modelClass)->addItem($data);
        } catch (ValidateException $e) {
            return $this->jsonErr($e->getError());
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        return $this->jsonOk();
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        $query = $this->modelClass::where(['id' => $id, 'school_id' => app()->user->schoolid]);
        return json((new $this->modelClass)->getItem($query));
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if (!(is_numeric($id) && ($id = intval($id)) > 0))  return $this->jsonErr('无效的id');

        $data = input('post.');
        try {
            validate($this->validateClass)->scene('update')->batch(true)->check($data);

            (new $this->modelClass)->updateItem($id, $data);
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
            validate($this->validateClass)->scene('del')->batch(true)->check($data);

            (new $this->modelClass)->delItem($id);
        } catch (ValidateException $e) {
            return $this->jsonErr($e->getError());
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }

        return $this->jsonOk();
    }
}
