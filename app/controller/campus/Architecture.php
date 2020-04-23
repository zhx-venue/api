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
        $departType = input('get.departmentType', 2, 'intval');
        $architecture = [];
        Contacts::getArchitecture($architecture, $departType);

        return json(Tree::makeTree($architecture, 'DepartmentId', 'ParentId'));
    }

    public function departments()
    {
        $daparts = [];
        $departType = input('get.departmentType', null, 'intval');
        Contacts::getDepartments($daparts, $departType);

        return json(Tree::makeTree($daparts, 'DepartmentId', 'ParentId'));
    }

    public function departmentUsers()
    {
        $users = [];
        $departmentId = input('get.departmentId', 0, 'intval');
        Contacts::getDepartUsers($departmentId, $users);

        return json($users);
    }
}
