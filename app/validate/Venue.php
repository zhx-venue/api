<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class Venue extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'id'   => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'type' => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'images' => ['require', 'checkImage'],
        'facility' => ['require', 'checkFacility'], 
        'opentime' => ['require', 'checkOpentime']
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
        'type.require'  => '场地类型必须',
        'type.regex'    => '无效的场地类型',
        'images.require'  => '场地图片必须',
        'facility.require'  => '场地设备必须',
        'opentime.require'  => '场地开放时间必须'
    ];

    // edit 验证场景定义
    public function sceneAdd()
    {
        return $this->only(['type', 'images', 'facility', 'opentime']);
    }

    // delete 验证场景定义
    public function sceneDel()
    {
    	return $this->only(['id']);
    }

    // update 验证场景定义
    public function sceneUpdate()
    {
        return $this->only(['images', 'facility', 'opentime'])
            ->remove('type', 'require')
            ->remove('images', 'require')
            ->remove('facility', 'require')
            ->remove('opentime', 'require');
    }

    protected function checkImage($value)
    {
        $bvalid = true;
        if (is_numeric($value) && intval($value) > 0) return true;
        if (is_array($value)) {
            foreach ($value as $_val) {
                if (!(is_numeric($_val) && intval($_val) > 0)) {
                    $bvalid = false;
                    break;
                }
            }
        } else { $bvalid = false; }

        return $bvalid ? true : '无效的场地图片';
    }

    protected function checkFacility($value)
    {
        $bvalid = true;
        if (is_array($value)) {
            $titles = [];
            foreach ($value as $_val) {
                if (isset($_val['title']) && is_string($_val['title']) && !empty($_val['title']) && !isset($titles[$_val['title']])) continue;

                $bvalid = false;
                $titles[$_val['title']] = 1;
                break;
            }
        } else { $bvalid = false; }

        return $bvalid ? true : '无效的场地名称';
    }

    protected function checkOpentime($value)
    {
        $bvalid = true;
        if (is_array($value)) {
            foreach ($value as $_val) {
                if (!(isset($_val['stime']) && is_numeric($_val['stime']) && isset($_val['etime']) && is_numeric($_val['etime'])) || $_val['stime'] > $_val['etime']) {
                    $bvalid = false;
                    break;
                }
            }
        } else { $bvalid = false; }

        return $bvalid ? true : '无效的开放时间';
    }
}
