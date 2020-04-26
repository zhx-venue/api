<?php
declare (strict_types = 1);

namespace app\model;

use think\facade\Db;
use app\BaseModel;
use app\campus\Contacts as CampusContacts;
use app\wxwork\Contacts as WxworkContacts;

/**
 * @mixin think\Model
 */
class VenueMember extends BaseModel
{
    /**
     * 格式化字段的查询条件
     */
    protected function formatFilter($field, $value) 
    {
        switch ($field) {
            case 'name' : { return ['like', '%'.$value.'%']; break; }
        }

        return null;
    }

    /**
     * 企业微信添加成员数据校验
     */
    private function _memberWork(&$members, &$validUsers)
    {
        // 校验企业微信成员信息
        foreach ($members as $_key => $_userInfo) {
            $userIds[$_userInfo['id']] = &$members[$_key];
        }
        if (!isset($userIds))    throw new \Exception('请选择要添加的成员');
        
        $users = WxworkContacts::authFocus(array_keys($userIds));
        foreach ($users['unfocus'] as $_userid) {
            $unfocus[] = $userIds[$_userid]['name'] ?? $_userid;
        }
        foreach ($users['outrange'] as $_userid) {
            $outrange[] = $userIds[$_userid]['name'] ?? $_userid;
        }
        foreach ($users['nonexist'] as $_userid) {
            $nonexist[] = $userIds[$_userid]['name'] ?? $_userid;
        }

        $errMsg = '';
        isset($unfocus) && $errMsg .= implode('、', $unfocus).' 尚未关注企业 ';
        isset($nonexist) && $errMsg .= implode('、', $nonexist).' 不在该企业 ';
        isset($outrange) && $errMsg .= implode('、', $outrange).' 不在应用可见范围 ';
        if (!empty($errMsg))    throw new \Exception($errMsg);

        foreach ($users['focus'] as $_userid) {
            $validUsers[$_userid] = [
                'corpid' => app()->user->corpid, 
                'userid' => $_userid, 
                'name' => $userIds[$_userid]['name'] ?? $_userid,
                'avatar' => $userIds[$_userid]['avatar'] ?? '',
            ];
        }
        if (empty($validUsers)) throw new \Exception('无效的成员');
    }

    /**
     * 智慧校园添加成员数据校验
     */
    private function _memberCampus(&$members, &$validUsers)
    {
        // 校验企业微信成员信息
        foreach ($members as $_key => $_userInfo) {
            $userIds[$_userInfo['OrgUserId']] = &$members[$_key];
        }
        if (!isset($userIds))    throw new \Exception('请选择要添加的成员');
        
        $users = CampusContacts::authFocus(array_keys($userIds));
        foreach ($users['unfocus'] as $_userid) {
            $unfocus[] = $userIds[$_userid]['Name'] ?? $_userid;
        }
        foreach ($users['outrange'] as $_userid) {
            $outrange[] = $userIds[$_userid]['Name'] ?? $_userid;
        }
        foreach ($users['nonexist'] as $_userid) {
            $nonexist[] = $userIds[$_userid]['Name'] ?? $_userid;
        }

        $errMsg = '';
        isset($unfocus) && $errMsg .= implode('、', $unfocus).' 尚未关注企业 ';
        isset($nonexist) && $errMsg .= implode('、', $nonexist).' 不在该企业 ';
        isset($outrange) && $errMsg .= implode('、', $outrange).' 不在应用可见范围 ';
        if (!empty($errMsg))    throw new \Exception($errMsg);

        foreach ($users['focus'] as $_userid) {
            isset($userIds[$_userid]['OpenUserId']) && $validUsers[$userIds[$_userid]['OpenUserId']] = [
                'openuserid' => $userIds[$_userid]['OpenUserId'], 
                'name' => $userIds[$_userid]['Name'] ?? $userIds[$_userid]['OpenUserId'],
                'avatar' => $userIds[$_userid]['avatar'] ?? '',
            ];
        }
        if (empty($validUsers)) throw new \Exception('无效的成员');
    }

    /**
     * 添加记录
     */
    public function addItem($data)
    {
        // 校验角色是否有效
        $roles = VenueRole::where(['id' => $data['role'], 'school_id' => app()->user->schoolid, 'status' => VenueRole::STATUS_NORMAL])->select();
        if ($roles->isEmpty())  throw new \Exception('无效的角色');

        $corpid = app()->user->corpid;
        if ($corpid) {
            $this->_memberWork($data['member'], $roleUsers);
        } else {
            $this->_memberCampus($data['member'], $roleUsers);
        }

        Db::startTrans();
        try {
            // 检查用户是否存在，不存在则新增用户
            VenueUser::extra('IGNORE')->limit(100)->insertAll(array_values($roleUsers));

            // 添加用户到学校成员
            $roleUsers = VenueUser::where($corpid ? ['corpid' => $corpid, 'userid' => array_keys($roleUsers)] : ['openuserid' => array_keys($roleUsers)])->select();
            foreach ($roleUsers as $_user) {
                $memberData[$_user->id] = [
                    'user_id' => $_user->id,
                    'school_id' => app()->user->schoolid,
                    'name' => $_user->getAttr('name'), 
                    'avatar' => $_user->avatar
                ];
            }

            if (isset($memberData)) {
                self::extra('IGNORE')->limit(100)->insertAll(array_values($memberData));

                // 添加成员角色
                $members = self::where(['school_id' => app()->user->schoolid, 'user_id' => array_keys($memberData)])->select();
                foreach ($members as $_member) {
                    $_member->status != self::STATUS_NORMAL && $_member->save(['status' => self::STATUS_NORMAL]);

                    $memIds[] = $_member->id;
                    foreach ($roles as $_role) {
                        $vrmData[] = ['rid' => $_role->id, 'mid' => $_member->id];
                    }
                }

                // 清除成员原来的角色
                isset($memIds) && VenueRoleMember::where(['mid' => $memIds])->delete();
                // 添加成员新角色
                isset($vrmData) && VenueRoleMember::extra('IGNORE')->limit(100)->insertAll($vrmData);
            }

            Db::commit(); // 提交事务
        } catch (\Exception $e) {
            Db::rollback(); // 回滚事务
            trace('添加成员失败:'.$e->getMessage(), 'error');
            throw new \Exception('添加成员失败');
        }
    }

    /**
     * 编辑记录
     */
    public function updateItem($id, $data)
    {
        $find = self::where(['id' => $id, 'school_id' => app()->user->schoolid])->find();
        if (empty($find))   throw new \Exception('成员不存在');

        // 校验角色是否有效
        $roles = VenueRole::where(['id' => $data['role'], 'school_id' => app()->user->schoolid])->select();
        if ($roles->isEmpty())  throw new \Exception('无效的角色');

        foreach ($roles as $_role) {
            $roleIds[$_role->id] = ['rid' => $_role->id, 'mid' => $find->id, 'created_by' => app()->user->id];
        }
        
        Db::startTrans();
        try {
            $roles = VenueRoleMember::where('mid', $find->id)->select();
            foreach ($roles as $_vrm) {
                if (isset($roleIds[$_vrm->rid])) {
                    unset($roleIds[$_vrm->rid]);
                } else {
                    $delIds[$_vrm->rid] = $_vrm->id;
                }
            }

            // 删除不需要的角色
            isset($delIds) && VenueRoleMember::destroy(array_values($delIds));
            // 添加新的角色
            empty($roleIds) || (new VenueRoleMember)->saveAll(array_values($roleIds));
            
            Db::commit(); // 提交事务
        } catch (\Exception $e) {
            Db::rollback(); // 回滚事务
            trace('更新成员失败:'.$e->getMessage(), 'error');
            throw new \Exception('更新成员失败');
        }
    }

    /**
     * 删除记录
     */
    public function delItem($id)
    {
        $find = self::where(['id' => $id, 'school_id' => app()->user->schoolid])->find();
        if ($find) {
            VenueRoleMember::where('mid', $find->id)->delete();
            $find->delete();
        }
    }

    /**
     * 清除学校所有成员
     */
    public static function clear($schoolId)
    {
        self::where('school_id', $schoolId)->chunk(100, function ($members) {
            foreach($members as $member){
                VenueRoleMember::where('mid', $member->id)->delete();
            }

            $members->delete();
        });
    }

    /**
     * 读取关联的用户
     */
    public function getUser()
    {
        $user = VenueUser::find($this->user_id);
        if ($user)  unset($user->password);

        return $user;
    }

    /**
     * 读取关联的角色
     */
    public function getRole()
    {
        return VenueRole::alias('vr')
            ->field('vr.*')
            ->join(VenueRoleMember::getTable().' vrm', 'vr.id=vrm.rid')
            ->where('vrm.mid', $this->id)
            ->order('vr.type')
            ->select();
    }
}
