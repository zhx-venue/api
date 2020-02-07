<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueSchool extends BaseModel
{
    /**
     * 格式化字段的查询条件
     */
    protected function formatFilter($field, $value) 
    {
        switch ($field) {
            case 'title' : { return ['like', '%'.$value.'%']; }
            case 'area_id':
            case 'city_id':
            case 'province_id': { return ['=', $value]; }
        }

        return null;
    }
}
