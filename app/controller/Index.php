<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use app\model\VenueRole;
use app\model\VenueRoleMember;
use app\model\VenueMember;
use app\validate\VenueMember as valVenueMember;
use app\model\VenueOrder;
use app\model\VenueVisitor;
use app\model\VenueType;
use app\model\VenueSchoolType;
use shophy\wxwork\structs\Agent;
use app\miniprogram\Api as MiniApi;
use think\exception\ValidateException;

class Index extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        // 默认控制器
        // 此控制器主要包含测试接口，无需调用相关中间件
        $this->middleware = [];
    }

    public function index()
    {
        var_dump(app());
    }

    /**
     * 测试使用
     */
    public function installed()
    {
        return json(input('get.'));
    }

    public function request()
    {
        dump($this->request);
    }

    public function phpinfo()
    {
        echo phpinfo();
    }

    public function asyncRet()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        json(['code' => 0, 'msg' => 'async return'])->send();

        usleep(5000000);
        trace('usleep trace');
    }
}
