<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueSchool extends BaseModel
{
    // 用户登录数据
    public static $conf = [
        // 单词预定场数上限
        'limit_order_count' => 1,
        // 单词预约时间上限
        'limit_order_hours' => 2,
        // 预期禁止预约次数
        'limit_breach' => 2
    ];


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

    /**
     * 更新学校设置
     */
    public static function updateConf(array $config, $schoolId=null)
    {
        $schoolId = intval($schoolId);
        $schoolId > 0 || $schoolId = intval(app()->user->schoolid);
        if ($schoolId <= 0) return false;

        $schoolInfo = self::where('id', $schoolId)->find();
        if (empty($schoolInfo)) return false;

        $filter = empty($schoolInfo->config) ? self::$conf : json_decode($schoolInfo->config, true);
        foreach ($config as $key => $value) {
            isset($filter[$key]) && $filter[$key] = $value;
        }

        $schoolInfo->config = json_encode($filter);
        return $schoolInfo->save();
    }

    public function getConfigArr()
    {
        return json_decode($this->config, true);
    }
}
