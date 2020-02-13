<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin think\Model
 */
class CorpAgent extends Model
{
    /**
     * 主键值
     * @var string
     */
    protected $pk = 'corpid';
}
