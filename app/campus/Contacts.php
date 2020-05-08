<?php

namespace app\campus;

use shophy\campus\Campus;
use shophy\campus\models\GetDptUsersRequest;
use shophy\campus\models\GetUsersInfoRequest;
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
    public static function getDepartments(&$daparts, $departType=null, $orgId=null)
    {
        $daparts = [];
        $pageSize = 64;
        $pageIndex = 1;
        
        try {
            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '', $orgId ?? app()->user->orgid);
            $request = new GetDepartmentListRequest();
            
            do {
                $request->deserialize([
                    'DepartmentType' => $departType ?? 0,
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
            throw $e;
        }
    }

    /**
     * 获取组织架构成员列表
     * @param $orgId 机构id
     * @param $departmentId 组织架构Id
     */
    public static function getDepartUsers($departmentId, &$users, $orgId=null)
    {
        $users = [];
        $pageSize = 64;
        $pageIndex = 1;

        try {
            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '', $orgId ?? app()->user->orgid);
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
            throw $e;
        }
    }

    /**
     * 读取组织架构
     */
    public static function getArchitecture(&$architecture, $departType=null, $orgId=null)
    {
        $architecture = [];
        self::getDepartments($architecture, null, $orgId); // 接口有问题，读取指定类型部门返回空数据
        foreach ($architecture as $key => $value) {
            $architecture[$key]['members'] = [];
            $value['UsersTotal'] > 0 && (is_null($departType) || $departType == 0 || $value['DepartmentType'] == $departType) && self::getDepartUsers($value['DepartmentId'], $architecture[$key]['members'], $orgId);
        }
    }

    /**
     * 检测用户是否在应用范围
     * @param array $userIds 这里是orgUserid
     */
    public static function authFocus($userIds, $orgId=null) 
    {
        $users = ['focus' => [], 'unfocus' => [], 'outrange' => [], 'nonexist' => []];
        try {
            $campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '', $orgId ?? app()->user->orgid);
            $request = new GetUsersInfoRequest();

            array_walk($userIds, function(&$v, $k) { $v = strval($v); });
            $userIds = array_chunk($userIds, 100);
            foreach ($userIds as $_userIds) {
                $request->deserialize(['OrgUserId' => $_userIds]);
                $response = $campus->GetUsersInfo($request);

                $oriUsers = array_flip($_userIds);
                foreach ($response->DataList as $_user) {
                    $users['focus'][] = $_user->OrgUserId;
                    unset($oriUsers[$_user->OrgUserId]);
                }

                $users['nonexist'] = array_merge($users['nonexist'], array_keys($oriUsers));
            }
        } catch (\Exception $e) {
            trace('[campus authFocus] - '.$e->getMessage(), 'error');
        }

        return $users;
    }
}
