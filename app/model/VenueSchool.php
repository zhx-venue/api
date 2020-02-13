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

    /**
     * 通过场地类型搜索学校
     * @param int $venueType
     */
    public function listByVenue($venueType, $page=1, $psize=self::SIZE_PER_PAGE)
    {
        $query = self::alias('vs')
            ->join(Venue::getTable().' v', 'v.school_id=vs.id')
            ->where(['v.type' => $venueType, 'v.status' => Venue::STATUS_NORMAL, 'vs.status' => self::STATUS_NORMAL]);

        $page = intval($page);
        $psize = intval($psize);
        $query->page($page > 0 ? $page : 1)->limit($psize > 0 ? $psize : self::SIZE_PER_PAGE);
        return $this->listItem($query);
    }
}
