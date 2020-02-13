<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class VenueMember extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'id'   => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'role' => ['require', 'checkRole'],
        'member' => ['require', 'checkMember']
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'id.require'    => '记录id必须',
        'id.regex'      => 'id必须大于0的整数',
        'role.require'  => '成员角色必须',
        'member'        => '成员必须'
    ];

    // edit 验证场景定义
    public function sceneAdd()
    {
        return $this->only(['role', 'member']);
    }

    // delete 验证场景定义
    public function sceneDel()
    {
    	return $this->only(['id']);
    }

    // update 验证场景定义
    public function sceneUpdate()
    {
    	return $this->only(['role'])->remove('role', 'require');
    }

    // 校验角色是否有效
    protected function checkRole($value)
    {
        $bvalid = true;
        if (is_array($value)) {
            foreach ($value as $_rid) {
                if (!(is_numeric($_rid) && intval($_rid) > 0)) {
                    $bvalid = false;
                    break;
                }
            }
        } else { $bvalid = false; }

        return $bvalid ? true : '无效的成员角色';
    }

    // 校验成员参数格式是否正确
    protected function checkMember($value)
    {
        $bvalid = true;
        if (is_array($value)) {
            foreach ($value as $_member) {
                if (!isset($_member['id'])) {
                    $bvalid = false;
                    break;
                }
            }
        } else { $bvalid = false; }

        return $bvalid ? true : '无效的成员信息';
    }
}
