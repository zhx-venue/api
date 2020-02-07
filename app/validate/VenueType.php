<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class VenueType extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'id'    => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'title' => ['require', 'max' => 32], 
        'order' => ['regex' => 'number']
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
        'title.require' => '类型名称必须', 
        'title.max'     => '类型名称最多不能超过32个字符',
    ];

    // edit 验证场景定义
    public function sceneAdd()
    {
        return $this->only(['title', 'order']);
    }

    // delete 验证场景定义
    public function sceneDel()
    {
    	return $this->only(['id']);
    }
}
