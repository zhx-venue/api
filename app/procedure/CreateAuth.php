<?php

namespace app\procedure;

use think\facade\Db;
use app\model\VenueUser;
use app\model\VenueMember;
use app\model\VenueSchool;
use app\model\VenueSchoolType;
use app\model\VenueRole;
use app\model\VenueRoleMember;

/**
 * 企业微信应用授权后的一些任务处理
 */
class CreateAuth
{
    public static function deal(&$permanentInfo)
    {
        Db::startTrans();
        try {
            // 新建管理员帐号
            $userInfo = VenueUser::where(['corpid' => $permanentInfo->auth_corp_info->corpid, 'userid' => $permanentInfo->auth_user_info->userid])->find();
            if (empty($userInfo)) {
                $userInfo = VenueUser::create([
                    'name' => $permanentInfo->auth_user_info->name,
                    'corpid' => $permanentInfo->auth_corp_info->corpid,
                    'userid' => $permanentInfo->auth_user_info->userid,
                    'avatar' => $permanentInfo->auth_user_info->avatar
                ]);
            }

            // 新建学校主体
            $schoolInfo = VenueSchool::where(['corpid' => $permanentInfo->auth_corp_info->corpid])->find();
            if (empty($schoolInfo)) {
                $schoolInfo = VenueSchool::create([
                    'title' => $permanentInfo->auth_corp_info->corp_name,
                    'corpid' => $permanentInfo->auth_corp_info->corpid,
                    'created_by' => $userInfo->id, 
                    'updated_by' => $userInfo->id
                ]);
            }

            // 添加管理员成员记录
            VenueMember::clear($schoolInfo->id);
            $member = VenueMember::create([
                'user_id' => $userInfo->id,
                'school_id' => $schoolInfo->id,
                'name' => $userInfo->name,
                'avatar' => $userInfo->avatar
            ]);

            // 初始化角色信息
            VenueRole::initData($schoolInfo->id);
            // 将管理员添加到管理角色中
            $managerRole = VenueRole::where(['school_id' => $schoolInfo->id, 'type' => VenueRole::TYPE_MANAGER])->find();
            $managerRole && VenueRoleMember::create(['rid' => $managerRole->id, 'mid' => $member->id], [], true);

            // 设置默认场地类型
            VenueSchoolType::initData($schoolInfo->id);

            Db::commit(); // 提交事务
        } catch (\Exception $e) {
            Db::rollback(); // 回滚事务
            trace('应用授权初始化学校信息错误:'.$e->getMessage(), 'error');
        }
    }
}
