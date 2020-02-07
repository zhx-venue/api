<?php

namespace app\wxwork;

use app\model\Corp;
use app\model\CorpAgent;
use app\model\CorpPrivilege;

/**
 * 企业微信通讯录操作类
 */
class Contacts
{
    /**
     * 是否是该企业，该应用的管理员
     * @return true or false
     */
    public static function isAdmin($userid, $corpid)
    {
        // 获取应用信息
        $agentInfo = CorpAgent::find($corpid);
        $wxServiceApi = empty($agentInfo) ? null : OAuth::getServiceInstance();
        if ($wxServiceApi) {
            try {
                $adminList = $wxServiceApi->GetAdminList($corpid, $agentInfo->agentid);
                foreach($adminList->admin as $appAdmin) {
                    if($appAdmin->userid == $userid) {
                        if($appAdmin->auth_type == 1)   return true;
                        break;
                    }
                }
            } catch (\Exception $e) { }
        }

        return false;
    }

    /**
     * 获取当前应用可见部门列表并缓存
     * @return Department array
     */
    public static function listDepartments($corpid='', $bflush=false)
    {
        empty($corpid) && $corpid = app()->user->corpid;
        if (empty($corpid)) return [];

        $cacheKey = $corpid.'Depart';
        if($bflush) {
            cache($cacheKey, null);
        } else {
            $departs = cache($cacheKey);
            if(! $departs) {
                $wxServiceApi = OAuth::getCorpInstance($corpid);
                try {
                    $departs = $wxServiceApi->DepartmentList();
                } catch (\Exception $e) {
                    $departs = [];
                }

                empty($departs) || cache($cacheKey, $departs);
            }

            return $departs;
        }
    }

    /**
     * 获取用户详情
     * @return null or User Object
     */
    public static function getUserDetail($userid, $corpid)
    {
        static $_userDetail = [];
        if(isset($_userDetail[$corpid][$userid])) {
            return $_userDetail[$corpid][$userid];
        } else {
            try {
                $wxServiceApi = OAuth::getCorpInstance($corpid);
                $_userDetail[$corpid][$userid] = $wxServiceApi->UserGet($userid);
                return $_userDetail[$corpid][$userid];
            } catch (\Exception $e) { }
        }
        
        return null;
    }

    /**
     * 检测用户是否在应用范围
     */
    public static function authFocus($userIds, $corpid='') 
    {
        $users = ['focus' => [], 'unfocus' => [], 'outrange' => [], 'nonexist' => []];

        empty($corpid) && $corpid = app()->user->corpid;
        if (empty($corpid)) return $users;
        
        $corpAPI = OAuth::getCorpInstance($corpid);
        if($corpAPI) {
            foreach($userIds as $_userid) {
                try {
                    $userInfo = $corpAPI->UserGet($_userid);
                    if(isset($userInfo->status) && $userInfo->status === 1) {
                        $users['focus'][] = $_userid;
                    } else {
                        $users['unfocus'][] = $_userid;
                    }
                } catch (\Exception $e) {
                    $errmsg = $e->getMessage();
                    $msg = json_decode(substr($e->getMessage(), 15), true);
                    if(strpos($errmsg,'60111'))
                    $msg['errcode'] == 60111 && $users['nonexist'][] = $_userid;
                    $msg['errcode'] == 60011 && $users['outrange'][] = $_userid;
                }
            }
        } else {
            $users['nonexist'] = $userIds;
        }

        return $users;
    }

    /**
     * 读取企业微信组织架构
     */
    public static function getArchitecture($corpid, &$departs, &$members)
    {
        $corpInfo = empty($corpid) ? null : Corp::find($corpid);
        if (empty($corpInfo)) return [];

        $corpAPI = OAuth::getCorpInstance($corpInfo->id);
        if (!$corpAPI)  return [];

        $corpPrivilege = CorpPrivilege::find($corpInfo->id);
        if (empty($corpPrivilege))  return [];

        $corpPrivilege->allow_user = json_decode($corpPrivilege->allow_user, true);
        foreach ($corpPrivilege->allow_user as $value) {
            if (empty($value))  continue;

            $_member = $corpAPI->UserGet($value);
            if (empty($_member))    continue;

            $members[$_member->userid] = [
                'id' => $_member->userid ?? '', 
                'name' => $_member->name ?? '', 
                'avatar' =>  $_member->avatar ?? ''
            ];
        }

        $departs = self::listDepartments($corpInfo->id);
        foreach ($departs as $key => $value) {
            $_members = $corpAPI->UserList($value['id'], 1);
            if(empty($_members))    continue;

            foreach ($_members as $_member) {
                $departs[$key]['members'][] = [
                    'id' => $_member->userid ?? '', 
                    'name' => $_member->name ?? '', 
                    'avatar' =>  $_member->avatar ?? ''
                ];

                if (isset($members[$_member->userid]))  unset($members[$_member->userid]);
            }
        }
    }
}
