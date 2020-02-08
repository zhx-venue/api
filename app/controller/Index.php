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

class index extends BaseController
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

    public function hello($name = 'ThinkPHP6')
    {
        // 启动事务
        Db::startTrans();
        try {
            $managerRole = VenueRole::where(['name' => '管理员', 'type' => VenueRole::TYPE_MANAGER])->find();
            $managerRole || $managerRole = VenueRole::create(['name' => '管理员', 'type' => VenueRole::TYPE_MANAGER]);
            $managerRole->status != VenueRole::STATUS_NORMAL && $managerRole->save(['status' => VenueRole::STATUS_NORMAL]);
            
            $securityRole = VenueRole::where(['name' => '安保人员', 'type' => VenueRole::TYPE_SECURITY])->find();
            $securityRole || $securityRole = VenueRole::create(['name' => '安保人员', 'type' => VenueRole::TYPE_SECURITY]);
            $securityRole->status != VenueRole::STATUS_NORMAL && $securityRole->save(['status' => VenueRole::STATUS_NORMAL]);

            $customRole = VenueRole::where(['name' => '自定义角色', 'type' => VenueRole::TYPE_CUNSTOM])->find();
            $customRole || $customRole = VenueRole::create(['name' => '自定义角色', 'type' => VenueRole::TYPE_CUNSTOM]);
            $customRole->status != VenueRole::STATUS_NORMAL && $customRole->save(['status' => VenueRole::STATUS_NORMAL]);

            $member1 = VenueMember::create(['user_id' => 1, 'name' => '三哥']);
            $member2 = VenueMember::create(['user_id' => 2, 'name' => '四哥']);
            $member3 = VenueMember::create(['user_id' => 3, 'name' => '五哥']);

            $roleMember = new VenueRoleMember();
            $roleMember->saveAll([
                ['rid' => $managerRole->id, 'mid' => $member1->id], 
                ['rid' => $managerRole->id, 'mid' => $member2->id], 
                ['rid' => $managerRole->id, 'mid' => $member3->id], 
                ['rid' => $securityRole->id, 'mid' => $member1->id], 
                ['rid' => $securityRole->id, 'mid' => $member2->id], 
                ['rid' => $securityRole->id, 'mid' => $member3->id], 
                ['rid' => $customRole->id, 'mid' => $member1->id], 
                ['rid' => $customRole->id, 'mid' => $member2->id], 
                ['rid' => $customRole->id, 'mid' => $member3->id]
            ]);
            
            Db::commit(); // 提交事务
        } catch (\Exception $e) {
            echo $e->getMessage();
            Db::rollback(); // 回滚事务
        }

        //return json($datas);
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
