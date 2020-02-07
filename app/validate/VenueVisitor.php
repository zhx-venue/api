<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class VenueVisitor extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'id'   => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'name' => ['require', 'max' => 32],
        'mobile' => ['mobile'], 
        'id_number' => ['idCard'], 
        'openid' => ['require', 'max' => 128]
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
        'name.require'  => '姓名必须',
        'name.max'      => '姓名最多不能超过25个字符',
        'openid.require'  => 'openid必须',
    ];

    // edit 验证场景定义
    public function sceneAdd()
    {
        return $this->only(['name', 'mobile', 'id_number', 'openid']);
    }

    // update 验证场景定义
    public function sceneUpdate()
    {
        return $this->only(['name', 'mobile', 'id_number', 'openid'])
            ->remove('name', 'require')
            ->remove('openid', 'require');
    }
}
