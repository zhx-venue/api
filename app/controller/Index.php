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
    // 重置中间件
    protected function _middleware() {}

    public function index()
    {
        $hours = [];
        $openTime = input('get.opentime', 0, 'intval');
        for ($i = 0; $i < 24; ++$i) {
            if (($openTime>>($i*2))&1 && ($openTime>>($i*2+1))&1)   $hours[] = $i;
        }
        return json($hours);
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
}
