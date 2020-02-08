<?php
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
        $model = new \app\model\Venue;

        $timeRange = [
            ['stime' => 1578963500, 'etime' => 1578974400],
            ['stime' => 1578981600, 'etime' => 1578992400],
            ['stime' => 1578999600, 'etime' => 1579006800]
        ];
        $model->open_time = $model->parseOpentime($timeRange);dump($model->open_time);
        dump($model->calculateMaxtime($model->open_time));

        $opentime = $model->getOpentime();dump($opentime);
        // VenueRole::initData(1);
        // VenueType::initData();
        // VenueSchoolType::initData(1);
        
        // $role = VenueRole::where('type', VenueRole::TYPE_MANAGER)->find();
        // dump($role->members);

        // VenueRole::clear(0);
        // VenueMember::clear(0);

        // try {
        //     $result = validate(valVenueMember::class)->batch(true)->check(
        //         ['name'  => 'thinkphp', 'email' => 'thinkphp@qq.com']
        //     );
        // } catch (ValidateException $e) {
        //     // 验证失败 输出错误信息
        //     dump($e->getError());
        // }
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
