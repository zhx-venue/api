<?php

namespace app\campus;

use think\facade\Db;
use app\model\VenueUser;
use app\model\VenueMember;
use app\model\VenueSchool;
use app\model\VenueSchoolType;
use app\model\VenueRole;
use app\model\VenueRoleMember;
use shophy\campus\Campus;
use shophy\campus\models\GetOrgInfoRequest;
use shophy\campus\models\GetChangeListRequest;

/**
 * 数据变更处理
 */
class DealChange
{
    public $campus;

    public function __construct()
    {
        $this->campus = new Campus(config('campus.appId') ?? '', config('campus.secretId') ?? '', config('campus.secretKey') ?? '');
    }

    /**
     * 处理数据变更同步
     * throw Exception
     */
    public function do($Seq, $OrgId=0)
    {
        $request = new GetChangeListRequest();
        $request->deserialize(['Seq' => $Seq]);

        $this->campus->orgId = $OrgId;
        $changeList = $this->campus->GetChangeList($request);
        foreach ($changeList->DataList as $_change) {
            $unsupported = false;
            switch ($_change->DataType) {
                case 1: { $this->createAuth($_change->OrgId, $_change->Key); break; }
                case 6: { $this->changeOrgInfo($_change->OrgId, $_change->Key); break; }
                default: {
                    $method = 'notify'.$_change->DataType.$_change->OpType;
                    if(method_exists($this, $method)) {
                        $this->$method($_change->OrgId, $_change->Key);
                    } else {
                        $unsupported = true;
                    }
                }
            }

            trace('['.($unsupported ? 'unsupported':'notify').' change] - '.json_encode($_change->serialize()), 'info');
        }
    }

    /**
     * 机构安装应用信息变更
     * 安装的时候调用已经授权的接口初始化所有数据
     */
    public function changeAuth($orgid, $key)
    {
        $this->campus->orgId = $orgid;
        $orgInfo = $this->campus->GetOrgInfo(new GetOrgInfoRequest());
        
        Db::startTrans();
        try {
            // 新建学校主体
            $schoolInfo = VenueSchool::where(['orgid' => $orgid])->find();
            if (empty($schoolInfo)) {
                $schoolInfo = VenueSchool::create([
                    'title' => $orgInfo->Name,
                    'orgid' => $orgid
                ]);
            }

            // 清理管理员成员记录
            VenueMember::clear($schoolInfo->id);

            // 初始化角色信息
            VenueRole::initData($schoolInfo->id);

            // 设置默认场地类型
            VenueSchoolType::initData($schoolInfo->id);

            Db::commit(); // 提交事务
        } catch (\Exception $e) {
            Db::rollback(); // 回滚事务
            trace('初始化机构数据失败:'.$e->getMessage(), 'error');
            throw $e;
        }
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
