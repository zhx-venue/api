<?php
declare(strict_types=1);

namespace app\controller\wxwork;

use app\BaseController;
use app\wxwork\Contacts;
use app\helper\Tree;

class Architecture extends BaseController
{
    public function getDeparts()
    {
        return json(Contacts::listDepartments());
    }

    public function getAll()
    {
        $members = [];
        $departs = [];
        Contacts::getArchitecture(app()->user->corpid, $departs, $members);

        if (!empty($departs)) {
            // 对部门列表进行无限多级分类
            $departs = Tree::makeTree($departs);
            if(!empty($members) && $departs[0]['parentid'] !== 0){
                //重新排列整合树
                $new_cateList[] =  [
                    'id'=>1,
                    'parentid'=>0,
                    'title'=>$corpInfo->corp_name,
                    'is_member'=>1,
                    'expanded'=>true,
                    'children'=>$departs,
                    'members'=>$members,
                ];
            }
            return $this->jsonOk(['data' => isset($new_cateList) ? $new_cateList : $departs], '部门获取成功');
        } else {
            if(!empty($members)){
                //重新排列整合树
                $departs[] =  [
                    'id'=>1,
                    'parentid'=>0,
                    'title'=>$corpInfo->corp_name,
                    'is_member'=>1,
                    'expanded'=>true,
                    'children'=>[],
                    'members'=>$members,
                ];
                return $this->jsonOk(['data' => $departs], '部门获取成功');
            }else{
                return $this->jsonErr('部门获取失败，请查看帮助：https://w.url.cn/s/Angxibr');
            }
        }
    }
}
