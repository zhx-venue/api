<?php

namespace app\campus;

use shophy\campus\models\ChangeInfo;

/**
 * 数据变更处理
 */
class DealChange
{
    public function do(ChangeInfo $changeInfo)
    {
        trace('[notify change] - '.json_encode($changeInfo->serialize()));
        switch ($changeInfo->DataType) {
            case 1: { return $this->createAuth($changeInfo->OrgId, $changeInfo->Key); }
            case 6: { return $this->changeOrgInfo($changeInfo->OrgId, $changeInfo->Key); }
            default: {
                $method = 'notify'.$changeInfo->DataType.$changeInfo->OpType;
                if(method_exists($this, $method)) {
                    return $this->$method($changeInfo->OrgId, $changeInfo->Key);
                }
            }
        }
        
        trace('[unsupported change] - '.json_encode($changeInfo->serialize()));
    }

    /**
     * 机构安装应用信息变更
     * 安装的时候调用已经授权的接口初始化所有数据
     */
    public function changeAuth($orgid, $key)
    {
    }

    /**
     * 学校属性变更
     * 更新数据的接口：GetOrgInfo
     */
    public function changeOrgInfo($orgid, $key)
    {

    }

    /**
     * 用户信息 - 新增
     * 更新数据的接口：GetUsersInfo
     * 对应的Key：orgUserId	
     */
    public function notify21($orgid, $key)
    {
    }

    /**
     * 用户信息 - 修改
     * 更新数据的接口：GetUsersInfo
     * 对应的Key：orgUserId	
     */
    public function notify22($orgid, $key)
    {
    }

    /**
     * 用户信息 - 删除
     * 更新数据的接口：GetUsersInfo
     * 对应的Key：orgUserId	
     */
    public function notify23($orgid, $key)
    {
    }

    /**
     * 组织架构及属性变更 - 新增
     * 更新数据的接口：Getdepartment
     * 对应的Key：departmentId
     */
    public function notify31($orgid, $key)
    {
    }

    /**
     * 组织架构及属性变更 - 修改
     * 更新数据的接口：Getdepartment
     * 对应的Key：departmentId
     */
    public function notify32($orgid, $key)
    {
    }

    /**
     * 组织架构及属性变更 - 删除
     * 更新数据的接口：Getdepartment
     * 对应的Key：departmentId
     */
    public function notify33($orgid, $key)
    {
    }

    /**
     * 组织架构上下级关系变更 - 新增
     * 更新数据的接口：Getdepartment
     * 对应的Key：departmentId
     */
    public function notify41($orgid, $key)
    {
    }

    /**
     * 组织架构上下级关系变更 - 修改
     * 更新数据的接口：Getdepartment
     * 对应的Key：departmentId
     */
    public function notify42($orgid, $key)
    {
    }

    /**
     * 组织架构上下级关系变更 - 删除
     * 更新数据的接口：Getdepartment
     * 对应的Key：departmentId
     */
    public function notify43($orgid, $key)
    {
    }

    /**
     * 班级任课老师对应关系变更 - 新增
     * 更新数据的接口：GetTeacherClass
     * 对应的Key：OrgUserId
     */
    public function notify51($orgid, $key)
    {
    }

    /**
     * 班级任课老师对应关系变更 - 修改
     * 更新数据的接口：GetTeacherClass
     * 对应的Key：OrgUserId
     */
    public function notify52($orgid, $key)
    {
    }

    /**
     * 班级任课老师对应关系变更 - 删除
     * 更新数据的接口：GetTeacherClass
     * 对应的Key：OrgUserId
     */
    public function notify53($orgid, $key)
    {
    }
}
