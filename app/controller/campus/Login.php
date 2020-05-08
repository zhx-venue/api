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
use app\campus\DealChange;
use app\campus\AccessToken;
use shophy\campus\Campus;
use shophy\campus\models\GetOrgAdminsRequest;
use shophy\campus\models\GetAccessTokenByCodeRequest;

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

            // 学校主体不存在则创建
            $schoolInfo = VenueSchool::where('orgid', $response->Session->OrgId)->find();
            if (empty($schoolInfo)) {
                (new DealChange)->changeAuth($response->Session->OrgId, '');
                $schoolInfo = VenueSchool::where('orgid', $response->Session->OrgId)->find();
                if (empty($schoolInfo)) return $this->jsonErr('无机构信息，请联系管理员重新安装');
            }

            $userInfo = VenueUser::where(['openuserid' => $response->Session->OpenUserId])->find();
            $membInfo = empty($userInfo) ? null : VenueMember::where(['school_id' => $schoolInfo->id, 'user_id' => $userInfo->id])->find();
            if (empty($userInfo) || empty($membInfo) || $membInfo->status != 1) {
                $isAdmin = false;
                $campus->orgId = $response->Session->OrgId;
                $orgAdmins = $campus->GetOrgAdmins(new GetOrgAdminsRequest());
                foreach ($orgAdmins->DataList as $_admin) {
                    if ($_admin->OpenUserId == $response->Session->OpenUserId) {
                        $isAdmin = true;
                        break;
                    }
                }
                if (!$isAdmin)  return $this->jsonErr('您尚未在系统中，请联系管理员添加到系统');

                empty($userInfo) && $userInfo = new VenueUser();
                $userInfo->name = $response->Session->UserName;
                $userInfo->openuserid = $response->Session->OpenUserId;
                isset($response->Session->ExtData->Avatar) && $userInfo->avatar = $response->Session->ExtData->Avatar;
                $userInfo->save();

                empty($membInfo) && $membInfo = new VenueMember();
                $membInfo->school_id = $schoolInfo->id;
                $membInfo->user_id = $userInfo->id;
                $membInfo->name = $userInfo->name;
                isset($response->Session->ExtData->Avatar) && $membInfo->avatar = $response->Session->ExtData->Avatar;
                $membInfo->save();

                // 将管理员添加到管理角色中
                $managerRole = VenueRole::where(['school_id' => $schoolInfo->id, 'type' => VenueRole::TYPE_MANAGER])->find();
                $managerRole && VenueRoleMember::create(['rid' => $managerRole->id, 'mid' => $membInfo->id], [], true);
                
                unset($userInfo);
                $userInfo = VenueUser::where(['openuserid' => $response->Session->OpenUserId])->find();
                if (empty($userInfo))   return $this->jsonErr('用户信息获取失败');
            }
        } catch (\Exception $e) {
            return $this->jsonErr('授权失败！'.$e->getMessage());
        }

        // 生成授权信息
        $userToken = User::generateToken($userInfo, [
            'orgid' => $response->Session->OrgId,
            'orgRoleId' => $response->Session->RoleId,
            'orgUserid' => $response->Session->OrgUserId,
            'tokenCacheKey' => AccessToken::cache($response->AccessToken, $response->ExpireIn),
            'schoolid' => $schoolInfo->id
        ]);

        // 输出学校信息
        $userToken['school'] = [
            'id' => $schoolInfo->id, 
            'name' => $schoolInfo->title
        ];
        // 读取在学校的身份
        $userToken['school']['member'] = VenueMember::where(['school_id' => $schoolInfo->id, 'user_id' => $userInfo->id])->find();

        // 获取用户权限信息
        $userToken['auths'] = VenueRole::getUserAuth($userInfo->id, $schoolInfo->id);

        return $this->jsonOk($userToken);
    }
}
