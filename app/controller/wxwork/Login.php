<?php
declare(strict_types=1);

namespace app\controller\wxwork;

use app\BaseController;
use app\model\User;
use app\model\Corp;
use app\model\CorpAgent;
use app\model\VenueUser;
use app\model\VenueMember;
use app\model\VenueSchool;
use app\model\VenueRole;
use app\model\VenueRoleMember;
use app\wxwork\OAuth;
use app\wxwork\Service;

class Login extends BaseController
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();

        $this->middleware = [];
    }

    public function tokenTest()
    {
        $corpid = input('get.corpid');
        $userid = input('get.userid');
        $userInfo = VenueUser::where(['corpid' => $corpid, 'userid' => $userid])->find();
        if (empty($userInfo))   return $this->jsonErr('无效的用户');

        $schoolInfo = VenueSchool::where('corpid', $corpid)->find();
        if (empty($schoolInfo)) return $this->jsonErr('企业号未正确安装德育管理应用，请联系管理员重新安装');
        
        // 生成授权信息
        $userToken = User::generateToken($userInfo, ['schoolid' => $schoolInfo->id]);

        // 输出学校信息
        $userToken['school'] = [
            'id' => $schoolInfo->id, 
            'title' => $schoolInfo->title
        ];
        // 读取在学校的身份
        $userToken['school']['member'] = VenueMember::where(['school_id' => $schoolInfo->id, 'user_id' => $userInfo->id])->find();

        // 获取用户权限信息
        $userToken['auths'] = VenueRole::getUserAuth($userInfo->id, $schoolInfo->id);

        return $this->jsonOk($userToken);
    }

    public function token()
    {
        $schoolInfo = null;
        if (!is_null($corpid = input('get.corpid'))) {
            $userInfo = $this->_getUserByCorpid($corpid, $schoolInfo);
        } elseif (!is_null($authCode = input('get.auth_code'))) {
            $userInfo = $this->_getUserByAuthCode($authCode, $schoolInfo);
        } elseif (!is_null($cipherText = input('get.cipher_text'))) {
            $userInfo = $this->_getUserByCipherText($cipherText, $schoolInfo);
        } else {
            return $this->jsonErr('无效的参数');
        }
        
        if ($userInfo instanceof \think\Response)   return $userInfo;

        // 生成授权信息
        $userToken = User::generateToken($userInfo, ['schoolid' => $schoolInfo->id]);

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

    private function _getUserByCorpid($corpid, VenueSchool &$schoolInfo=null)
    {
        $code = input('get.code');
        if (empty($corpid) || empty($code)) return $this->jsonErr('invalid code');

        $corpInfo = Corp::find($corpid);
        if (empty($corpInfo))   return $this->jsonErr('无效的CORPID');
        $corpAgent = CorpAgent::find($corpInfo->corpid);
        if (empty($corpAgent))  return $this->jsonErr('企业号未安装德育管理应用，请联系管理员');
        $schoolInfo = VenueSchool::where('corpid', $corpInfo->corpid)->find();
        if (empty($schoolInfo)) return $this->jsonErr('企业号未正确安装德育管理应用，请联系管理员重新安装');

        try {
            $corpApi = OAuth::getCorpInstance($corpInfo->corpid);
            $wxUserInfo = $corpApi ? $corpApi->GetUserInfoByCode($code) : null;
            if (empty($wxUserInfo)) return $this->jsonErr('获取企业成员信息失败');
            if (!isset($wxUserInfo->UserId))    return $this->jsonErr('非企业成员无权限访问');
        } catch (\Exception $e) {
            return $this->jsonErr('企业用户信息获取失败'.$e->getMessage());
        }

        $userInfo = VenueUser::where(['corpid' => $corpInfo->corpid, 'userid' => $wxUserInfo->UserId])->find();
        $membInfo = empty($userInfo) ? null : VenueMember::where(['school_id' => $schoolInfo->id, 'user_id' => $userInfo->id])->find();
        if (empty($userInfo) || empty($membInfo) || $membInfo->status != 1) {
            $isAdmin = false;
            try {
                $corpApi = new Service($corpInfo->corpid, $corpAgent->permanent_code);
                // 获取用户信息
                $wxUserInfo = $corpApi->UserGet($wxUserInfo->UserId);
                // 获取应用管理员列表
                $agentAdmins = $corpApi->GetAdminList($corpInfo->corpid, $corpAgent->agentid);
                foreach ($agentAdmins->admin as $_admin) {
                    if ($_admin->auth_type == 1 && $_admin->userid == $wxUserInfo->userid) {
                        $isAdmin = true;
                        break;
                    }
                }
                if (!$isAdmin)  return $this->jsonErr('您尚未在系统中，请联系管理员添加到系统');

                empty($userInfo) && $userInfo = new VenueUser();
                $userInfo->name = $wxUserInfo->name;
                $userInfo->corpid = $corpInfo->corpid;
                $userInfo->userid = $wxUserInfo->userid;
                empty($wxUserInfo->email) || $userInfo->email = $wxUserInfo->email;
                empty($wxUserInfo->avatar) || $userInfo->avatar = $wxUserInfo->avatar;
                $userInfo->save();

                empty($membInfo) && $membInfo = new VenueMember();
                $membInfo->school_id = $schoolInfo->id;
                $membInfo->user_id = $userInfo->id;
                $membInfo->name = $userInfo->name;
                $membInfo->save();

                // 将管理员添加到管理角色中
                $managerRole = VenueRole::where(['school_id' => $schoolInfo->id, 'type' => VenueRole::TYPE_MANAGER])->find();
                $managerRole && VenueRoleMember::create(['rid' => $managerRole->id, 'mid' => $membInfo->id], [], true);
            } catch (\Exception $e) {
                return $this->jsonErr('用户信息获取失败'.$e->getMessage());
            }
            
            unset($userInfo);
            $userInfo = VenueUser::where(['corpid' => $corpInfo->corpid, 'userid' => $wxUserInfo->userid])->find();
            if (empty($userInfo))   return $this->jsonErr('用户信息获取失败');
        }

        return $userInfo;
    }

    private function _getUserByAuthCode($auth_code, VenueSchool &$schoolInfo=null)
    {
        if (empty($auth_code))  return $this->jsonErr('无效的参数');

        try {
            $corpApi = OAuth::getProviderInstance();
            $wxUserInfo = $corpApi ? $corpApi->GetLoginInfo($auth_code) : null;
            if (empty($wxUserInfo)) return $this->jsonErr('获取企业成员信息失败');
        } catch (\Exception $e) {
            trace('获取企业微信用户信息失败：'.$e->getMessage(), 'error');
            return $this->jsonErr('企业用户信息获取失败');
        }

        $schoolInfo = VenueSchool::where('corpid', $wxUserInfo->corp_info->corpid)->find();
        if (empty($schoolInfo))return $this->jsonErr('企业号未正确安装德育管理应用，请联系管理员重新安装');

        $userInfo = VenueUser::where(['corpid' => $wxUserInfo->corp_info->corpid, 'userid' => $wxUserInfo->user_info->userid])->find();
        $membInfo = empty($userInfo) ? null : VenueMember::where(['school_id' => $schoolInfo->id, 'user_id' => $userInfo->id])->find();
        if (empty($userInfo) || empty($membInfo) || $membInfo->status != 1) {
            $bAgentAdmin = false;
            if (isset($wxUserInfo->usertype)) {
                in_array($wxUserInfo->usertype, [1,2,3]) && $bAgentAdmin = true;
                if (!$bAgentAdmin && $wxUserInfo->usertype == 4) {
                    $agentInfo = CorpAgent::find($corpInfo->corpid);
                    if (!empty($agentInfo)) {
                        foreach ($wxUserInfo->agent as $_agent) {
                            if ($_agent->agentid == $agentInfo->agentid && $_agent->auth_type == 1) {
                                $bAgentAdmin = true;
                                break;
                            }
                        }
                    }
                }
            }
            if (!$bAgentAdmin)  return $this->jsonErr('用户信息获取失败');

            try {
                empty($userInfo) && $userInfo = new VenueUser();
                $userInfo->name = $wxUserInfo->user_info->name;
                $userInfo->corpid = $wxUserInfo->corp_info->corpid;
                $userInfo->userid = $wxUserInfo->user_info->userid;
                empty($wxUserInfo->user_info->email) || $userInfo->email = $wxUserInfo->user_info->email;
                empty($wxUserInfo->user_info->avatar) || $userInfo->avatar = $wxUserInfo->user_info->avatar;
                $userInfo->save();

                empty($membInfo) && $membInfo = new VenueMember();
                $membInfo->school_id = $schoolInfo->id;
                $membInfo->user_id = $userInfo->id;
                $membInfo->name = $userInfo->name;
                $membInfo->save();

                // 将管理员添加到管理角色中
                $managerRole = VenueRole::where(['school_id' => $schoolInfo->id, 'type' => VenueRole::TYPE_MANAGER])->find();
                $managerRole && VenueRoleMember::create(['rid' => $managerRole->id, 'mid' => $membInfo->id], [], true);
            } catch (\Exception $e) {
                trace('初始化用户信息失败：'.$e->getMessage(), 'error');
                return $this->jsonErr('用户信息获取失败');
            }

            unset($userInfo);
            $userInfo = VenueUser::where(['corpid' => $wxUserInfo->corp_info->corpid, 'userid' => $wxUserInfo->user_info->userid])->find();
            if (empty($userInfo))   return $this->jsonErr('用户信息获取失败');
        }
        
        return $userInfo;
    }

    private function _getUserByCipherText($cipherText, VenueSchool &$schoolInfo=null)
    {
        if (empty($cipherText)) return $this->jsonErr('cipher_text不能为空');

        $authData = base64_decode(urldecode($cipherText));
        $authData && $authData = json_decode($authData, true);
        if (empty($authData))   return $this->jsonErr('无效的参数');

        $schoolInfo = VenueSchool::where('corpid', $authData['corpid'])->find();
        if (empty($schoolInfo)) return $this->jsonErr('企业号未正确安装德育管理应用，请联系管理员重新安装');
        $userInfo = VenueUser::where(['corpid' => $authData['corpid'], 'userid' => $authData['userid']])->find();
        if (empty($userInfo))   return $this->jsonErr('用户信息获取失败');

        return $userInfo;
    }
}
