<?php
declare(strict_types=1);

namespace app\controller\campus;

use app\BaseController;
use app\campus\Contacts;
use app\helper\Tree;

class Architecture extends BaseController
{
    public function all()
    {
        $architecture = [];
        Contacts::getArchitecture(app()->user->orgid, $architecture);

        return json(Tree::makeTree($architecture, 'DepartmentId', 'ParentId'));
    }

    public function departments()
    {
        $daparts = [];
        Contacts::getDepartments(app()->user->orgid, $daparts);

        return json(Tree::makeTree($daparts, 'DepartmentId', 'ParentId'));
    }

    public function departmentUsers()
    {
        $users = [];
        $departmentId = input('get.departmentId', 0, 'intval');
        Contacts::getDepartUsers(app()->user->orgid, $departmentId, $users);

        return json($users);
    }
}
