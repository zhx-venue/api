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
use shophy\campus\Campus;
use shophy\campus\models\GetAccessTokenByCodeRequest;
use shophy\campus\models\GetOrgAdminsRequest;

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

    public function login()
    {
        try {
            $request = new GetAccessTokenByCodeRequest();
            $request->deserialize(['UserCode' => input('get.code', null, 'strval')]);

            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '');
            $response = $campus->GetAccessTokenByCode($request);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
        
        return json($response->serialize());
    }

    public function test()
    {
        try {
            $request = new GetOrgAdminsRequest();
            // $request->deserialize([
            //     'Seq' => input('get.seq', null, 'strval')
            // ]);

            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '');
            $campus->orgId = input('get.orgid', 0, 'intval');
            $response = $campus->GetOrgAdmins($request);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
        
        return json($response->serialize());
    }

    public function index()
    {
        $hours = [];
        $openTime = input('get.opentime', 0, 'intval');
        for ($i = 0; $i < 24; ++$i) {
            if (($openTime>>($i*2))&1 && ($openTime>>($i*2+1))&1)   $hours[] = $i;
        }
        return json($hours);
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
