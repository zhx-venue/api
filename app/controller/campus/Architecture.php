<?php
declare(strict_types=1);

namespace app\controller\campus;

use app\BaseController;
use app\campus\Contacts;
use app\helper\Tree;
use app\model\VenueRole;

class Architecture extends BaseController
{
    public function all()
    {
        if (!checkAuth(VenueRole::MD_MEMBER, 1))   return $this->jsonErr('无权限进行该操作');

        $departType = input('get.departmentType', 2, 'intval');
        $architecture = [];

        try {
            Contacts::getArchitecture($architecture, $departType);
        } catch (\Exception $e) {
            return $this->jsonErr('获取组织架构失败！'.$e->getMessage());
        }
        
        return json(Tree::makeTree($architecture, 'DepartmentId', 'ParentId'));
    }

    public function departments()
    {
        if (!checkAuth(VenueRole::MD_MEMBER, 1))   return $this->jsonErr('无权限进行该操作');

        $daparts = [];
        $departType = input('get.departmentType', null, 'intval');

        try {
            Contacts::getDepartments($daparts, $departType);
        } catch (\Exception $e) {
            return $this->jsonErr('获取部门列表失败！'.$e->getMessage());
        }

        return json(Tree::makeTree($daparts, 'DepartmentId', 'ParentId'));
    }

    public function departmentUsers()
    {
        if (!checkAuth(VenueRole::MD_MEMBER, 1))   return $this->jsonErr('无权限进行该操作');
        
        $users = [];
        $departmentId = input('get.departmentId', 0, 'intval');

        try {
            Contacts::getDepartUsers($departmentId, $users);
        } catch (\Exception $e) {
            return $this->jsonErr('获取部门成员失败！'.$e->getMessage());
        }

        return json($users);
    }
}
