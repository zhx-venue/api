<?php
declare (strict_types = 1);

namespace app\model;

use think\model\Pivot;

/**
 * @mixin think\Model
 */
class VenueRoleMember extends Pivot
{
    /**
     * 创建时间字段 false表示关闭
     * @var false|string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段 false表示关闭
     * @var false|string
     */
    protected $updateTime = 'updated_at';

    protected $autoWriteTimestamp = true;
}
