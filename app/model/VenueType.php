<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueType extends BaseModel
{
    public static $default = [
        '篮球场',
        '足球场',
        '网球场',
        '乒乓球场',
        '羽毛球场',
    ];

    /**
     * 初始化学校默认的场地类型
     * @param $schoolId 学校记录ID
     */
    public static function initData()
    {
        foreach (self::$default as $_type) {
            $find = self::where('title', $_type)->find();
            $find || self::create(['title' => $_type, 'position' => 1]);
        }
    }
}
