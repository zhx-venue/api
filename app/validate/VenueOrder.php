<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;
use app\model\VenueOrder as MVenueOrder;

class VenueOrder extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'id'   => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'facility_id' => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'people_counts' => ['require', 'regex' => '^[0-9]*[1-9][0-9]*$'],
        'order_time' => ['require', 'checkOrderTime'], 
        'process' => ['checkProcess']
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
        'facility_id.require'  => '场地必须',
        'facility_id.regex'    => '场地id必须大于0的整数',
        'people_counts.require'=> '到场人数必须',
        'people_counts.regex'  => '到场人数必须大于0的整数',
        'order_time.require'   => '预定时间必须'
    ];

    // edit 验证场景定义
    public function sceneAdd()
    {
        return $this->only(['facility_id', 'people_counts', 'order_time']);
    }

    // delete 验证场景定义
    public function sceneDel()
    {
    	return $this->only(['id']);
    }

    // update 验证场景定义
    public function sceneUpdate()
    {
        return $this->only(['process']);
    }

    protected function checkProcess($value)
    {
        return in_array(intval($value), [
            MVenueOrder::PROCESS_CANCEL,
            MVenueOrder::PROCESS_CHECKING,
            MVenueOrder::PROCESS_SIGNING,
            MVenueOrder::PROCESS_SIGNOUTING,
            MVenueOrder::PROCESS_REVOKED,
            MVenueOrder::PROCESS_SIGNOUTED
        ]) ? true : '无效的预约进度';
    }

    protected function checkOrderTime($value)
    {
        if (is_array($value)) {
            $now = time();
            if (isset($value['stime']) && is_numeric($value['stime']) && intval($value['stime']) > $now) {
                if (isset($value['etime']) && is_numeric($value['etime']) && intval($value['etime']) > $now) {
                    if (date('Ymd', $value['stime']) == date('Ymd', $value['etime'])) {
                        return true;
                    }
                }
            }
        }

        return '无效的预定时间';
    }
}
