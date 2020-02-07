<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueRole extends BaseModel
{
    // 角色类型
    const TYPE_MANAGER = 1; // 管理员
    const TYPE_SECURITY = 2; // 安保人员
    const TYPE_CUNSTOM = 6; // 自定义角色

    /** 
     * 角色所有的权限模块与操作权限
    */
    const MD_SYS = 1; // 系统管理
    const MD_VENUE = 2; // 场地管理
    const MD_ORDER = 3; // 预约管理
    const MD_MEMBER = 4; // 成员管理
    const MD_SETTING = 5; // 学校设置

    /**
     * _t : 权限标题
     * _dv: 权限默认值
     */
    public static $authority = [
        self::MD_SYS => [
            '_t' => '系统管理', 
            '_p' => [
                0 => ['_t' => '预约设置', '_dv' => 0], 
                1 => ['_t' => '访客黑名单', '_dv' => 0], 
            ]
        ], 
        self::MD_VENUE => [
            '_t' => '场地管理', 
            '_p' => [
                0 => ['_t' => '查看', '_dv' => 0], 
                1 => ['_t' => '管理', '_dv' => 0],
                2 => ['_t' => '类别管理', '_dv' => 0],
            ]
        ], 
        self::MD_ORDER => [
            '_t' => '预约管理', 
            '_p' => [
                0 => ['_t' => '查看所有预约', '_dv' => 0], 
                1 => ['_t' => '预约', '_dv' => 1], 
                2 => ['_t' => '核验', '_dv' => 0],
                3 => ['_t' => '审核', '_dv' => 0],
                4 => ['_t' => '统计', '_dv' => 0]
            ]
        ], 
        self::MD_MEMBER => [
            '_t' => '成员管理', 
            '_p' => [
                0 => ['_t' => '查看', '_dv' => 0], 
                1 => ['_t' => '管理', '_dv' => 0]
            ]
        ], 
        self::MD_SETTING => [
            '_t' => '学校设置', 
            '_p' => [
                0 => ['_t' => '编辑', '_dv' => 0]
            ]
        ]
    ];

    /**
     * 读取角色的权限
     */
    public function getAuths()
    {
        VenueRoleAuth::where('rid', $this->id)->chunk(100, function ($modules) {
            foreach($modules as $value){
                $authData[$value->module] = $value->auth_data;
            }
        });
        foreach (self::$authority as $module => $positions) {
            foreach ($positions['_p'] as $pos => $authInfo) {
                isset($authData[$module]) && $authData[$module] & (1 << $pos) && $auths[] = self::generateFlag($module, $pos);
            }
        }

        return isset($auths) ? $auths : [];
    }

    /**
     * 检测权限数据中是否有对应模块的权限
     */
    public static function checkAuth($module, $position, &$auths)
    {
        // 管理员拥有所有权限
        if ($auths['pos'] & 1)  return true;
        // 安保人员可以查看所有预约、核验、统计
        if ($auths['pos'] & 2 && ($module == self::MD_ORDER && in_array($positions, [0,2,4])))  return true;

        if (is_null($position)) {
            $isInAuth = isset($auths['module'][$module]);
        } else {
            $position = intval($position);
            $isInAuth = isset($auths['module'][$module]) && ($auths['module'][$module] & (1<<$position));
        }
        
        return $isInAuth;
    }

    /**
     * 读取用户在学校的综合权限
     */
    public static function getUserAuth($uid=0, $schoolId=0)
    {
        static $_auths = [];

        $uid = intval($uid);
        $uid > 0 || $uid = intval(app()->user->id);
        $schoolId = intval($schoolId);
        $schoolId > 0 && $schoolId = intval(app()->user->schoolid);
        if ($uid <= 0 || $schoolId <= 0)    return ['pos' => 0, 'module' => []];
        if (isset($_auths[$uid][$schoolId]))    return $_auths[$uid][$schoolId];

        $_auths[$uid][$schoolId] = ['pos' => 0, 'module' => []];
        $userInfo = VenueUser::find($uid);
        if (empty($userInfo))   return $_auths[$uid][$schoolId];

        // 超级管理员和客服人员拥有所有权限
        if (in_array($userInfo->type, [VenueUser::TYPE_ADMIN, VenueUser::TYPE_CUSTOMER])) {
            $_auths[$uid][$schoolId]['pos'] |= 1;
            return $_auths[$uid][$schoolId];
        }

        $memInfo = VenueMember::where(['school_id' => $schoolId, 'user_id' => $uid])->find();
        if (empty($memInfo))    return $_auths[$uid][$schoolId];

        foreach ($memInfo->roles as $role) {
            switch($role->type) {
                case self::TYPE_MANAGER: { $_auths[$uid][$schoolId]['pos'] |= 1; return $_auths[$uid][$schoolId]; }
                case self::TYPE_SECURITY: { $_auths[$uid][$schoolId]['pos'] |= 2; break; }
                default: { $roleIds[] = $role->id; }
            }
        }
        if (!isset($roleIds))   return $_auths[$uid][$schoolId];

        VenueRoleAuth::where(['rid' => $roleIds])->chunk(100, function ($modules) {
            foreach($modules as $value){
                if (isset($_auths[$uid][$schoolId]['module'][$value->module])) {
                    $_auths[$uid][$schoolId]['module'][$value->module] |= $value->auth_data;
                } else {
                    $_auths[$uid][$schoolId]['module'][$value->module] = $value->auth_data;
                }
            }
        });

        return $_auths[$uid][$schoolId];
    }

    /**
     * 检测用户权限
     * @param $module 要检测的模块
     * @param $postion 要检测的子模块所在的标志位,null:不检测子模块
     */
    public static function checkUserAuth($module, $position, $uid='', $schoolId='')
    {
        $auths = self::getUserAuth($uid, $schoolId);
        return self::checkAuth($module, $position, $auths);
    }

    /**
     * 初始化默认角色
     */
    public static function initData($schoolId)
    {
        $default = [
            ['name' => '管理员', 'type' => self::TYPE_MANAGER], 
            ['name' => '安保人员', 'type' => self::TYPE_SECURITY]
        ];
        foreach ($default as $value) {
            $find = self::where(['school_id' => $schoolId, 'type' => $value['type']])->find();
            if (!$find) {
                self::create(['school_id' => $schoolId, 'type' => $value['type'], 'name' => $value['name']]);
            } elseif ($find->status != self::STATUS_NORMAL) {
                $find->save(['status' => self::STATUS_NORMAL]);
            }
        }
    }

    /**
     * 清除角色数据
     */
    public static function clear($schoolId)
    {
        // 删除自定义角色
        $roles = self::where(['school_id' => $schoolId, 'type' => self::TYPE_CUNSTOM, 'status' => self::STATUS_NORMAL])->select();
        foreach ($roles as $role) {
            VenueRoleAuth::where('rid', $role->id)->delete();
            VenueRoleMember::where('rid', $role->id)->delete();
        }
        $roles->update(['status' => self::STATUS_DELETE]);

        // 默认角色成员清除
        $roles = self::where(['school_id' => $schoolId, 'status' => self::STATUS_NORMAL])->select();
        foreach ($roles as $role) {
            VenueRoleMember::where('rid', $role->id)->delete();
        }
    }

    /**
     * 通过角色成员表关联成员表
     */
    public function members()
    {
        return $this->belongsToMany(VenueMember::class, VenueRoleMember::class, 'mid', 'rid');
    }

    /**
     * 生成权限标识
     * @param $module 模块值
     * @param $position 权限标志位
     */
    public static function generateFlag($module, $position)
    {
        return intval(($module << 8) | $position);
    }

    /**
     * 解析权限标识
     * @param $flag 标识
     * @return [$module, $postion]
     */
    public static function parseFlag($flag)
    {
        return [$flag >> 8, $flag & 255];
    }
}
