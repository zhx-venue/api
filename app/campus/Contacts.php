<?php

namespace app\campus;

use shophy\campus\Campus;
use shophy\campus\models\GetDptUsersRequest;
use shophy\campus\models\GetDepartmentListRequest;

/**
 * 智慧校园2.0组织架构操作类
 */
class Contacts
{
    /**
     * 获取组织架构列表
     * @param $orgId 机构id
     * @param $departType 架构类型
     * 全部:0,学生:1,教职工:2,校友:4,退休教师:5,临时组:6,虚拟组:7,课程班:8,上级单位:9,教学班（高校）:10
     */
    public static function getDepartments($orgId, &$daparts, $departType=0)
    {
        $daparts = [];
        $pageSize = 64;
        $pageIndex = 1;
        
        try {
            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '', $orgId);
            $request = new GetDepartmentListRequest();
            
            do {
                $request->deserialize([
                    'DepartmentType' => $departType,
                    'PageIndex' => $pageIndex,
                    'PageSize' => $pageSize
                ]);
                $response = $campus->GetDepartmentList($request);
                foreach ($response->Departments as $_depart) {
                    $daparts[] = $_depart->serialize();
                }
            } while (($pageSize*$pageIndex++) < $response->Total);
        } catch (\Exception $e) {
            trace('[get department list] - '.$e->getMessage(), 'error');
        }
    }

    /**
     * 获取组织架构成员列表
     * @param $orgId 机构id
     * @param $departmentId 组织架构Id
     */
    public static function getDepartUsers($orgId, $departmentId, &$users)
    {
        $users = [];
        $pageSize = 64;
        $pageIndex = 1;

        try {
            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '', $orgId);
            $request = new GetDptUsersRequest();

            do {
                $request->deserialize([
                    'DepartmentId' => $departmentId,
                    'PageIndex' => $pageIndex,
                    'PageSize' => $pageSize
                ]);
                $response = $campus->GetDptUsers($request);
                foreach ($response->UserInfos as $_user) {
                    $users[] = $_user->serialize();
                }
            } while (($pageSize*$pageIndex++) < $response->Total);
        } catch (\Exception $e) {
            trace('[get depart user list] - '.$e->getMessage(), 'error');
        }
    }

    /**
     * 读取组织架构
     */
    public static function getArchitecture($orgId, &$architecture, $departType=null)
    {
        $architecture = [];
        self::getDepartments($orgId, $architecture);
        foreach ($architecture as $key => $value) {
            $architecture[$key]['members'] = [];
            (is_null($departType) || $value['DepartmentType'] == $departType) && self::getDepartUsers($orgId, $value['DepartmentId'], $architecture[$key]['members']);
        }
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
}
