<?php
declare(strict_types=1);

namespace app\controller\campus;

use app\BaseController;
use app\model\User;
use app\model\VenueUser;
use app\model\VenueMember;
use app\model\VenueSchool;
use app\model\VenueRole;
use app\model\VenueRoleMember;

class Login extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }

    public function index()
    {
        $appid = input('get.appid', 0, 'intval');
        if ($appid != config('campus.appId'))   return $this->jsonErr('无效的appid');

        $code = input('get.code', '', 'strval');
        if (empty($code))   return $this->jsonErr('无效的code');

        try {
            $request = new GetAccessTokenByCodeRequest();
            $request->deserialize(['UserCode' => $code]);

            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '');
            $response = $campus->GetAccessTokenByCode($request);
        } catch (\Exception $e) {
            return $this->jsonErr($e->getMessage());
        }
        
        return json($response->serialize());
    }
}
