<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;
use think\facade\Db;

/**
 * @mixin think\Model
 */
class Venue extends BaseModel
{
    /**
     * 格式化字段的查询条件
     */
    protected function formatFilter($field, $value) 
    {
        switch ($field) {
            case 'type' : {
                $value = is_array($value) ? $value : array_keys(array_flip(array_filter(explode(',', strval($value)))));
                return count($value) > 1 ? ['in', $value] : ['=', array_shift($value)];
            }
            case 'school_id' : { return ['=', intval($value)]; }
        }

        return null;
    }

    /**
     * 添加记录
     */
    public function addItem($data)
    {
        // 场地类型校验
        $venueType = VenueType::find($data['type']);
        if (empty($venueType))  throw new \Exception('不存在的场地类型');

        // 场地图片校验
        $venueImage = VenueFile::where(['id' => $data['images']])->select();
        if ($venueImage->isEmpty()) throw new \Exception('不存在的场地图片');

        // 开放时间校验
        $opentime = self::parseOpentime($data['opentime']);
        if ($opentime <= 0)   throw new \Exception('请选择开放时间');

        $option = 0;
        (isset($data['bopen']) && $data['bopen'] == 0) && $option |= 1;
        (isset($data['binside']) && $data['binside'] == 0) && $option |= 2;

        $baseData = [
            'school_id' => app()->user->schoolid ?? 0,
            'type' => $data['type'], 
            'open_time' => $opentime, 
            'max_continuous' => $this->calculateMaxtime($opentime), 
            'limit_ordertime' => $data['limit_ordertime'] ?? 0,
            'option' => $option,
            'created_by' => app()->user->id ?? 0,
            'updated_by' => app()->user->id ?? 0
        ];

        Db::startTrans();
        try {
            // 添加场地记录
            $venueInfo = self::create($baseData);

            // 添加场地设备
            $venueFacilitys = [];
            foreach ($data['facility'] as $_facility) {
                $facilityInfo = array_merge($baseData, ['venue_id' => $venueInfo->id, 'title' => $_facility['title']]);
                if (isset($_facility['bopen'])) {
                    $_facility['bopen'] == 0 ? ($facilityInfo['option'] |= 1) : ($facilityInfo['option'] &= ~1);
                }
                if (isset($_facility['binside'])) {
                    $_facility['binside'] == 0 ? ($facilityInfo['option'] |= 2) : ($facilityInfo['option'] &= ~2);
                }
                if (isset($_facility['opentime'])) {
                    $opentime = self::parseOpentime($_facility['opentime']);
                    if ($opentime > 0) {
                        $facilityInfo['open_time'] = $opentime;
                        $facilityInfo['max_continuous'] = $this->calculateMaxtime($opentime);
                    }
                }
                if (isset($_facility['limit_ordertime'])) {
                    $facilityInfo['limit_ordertime'] = $_facility['limit_ordertime'];
                }

                $venueFacilitys[] = $facilityInfo;
            }
            empty($venueFacilitys) || (new VenueFacility)->saveAll($venueFacilitys);

            // 添加场地图片
            if (is_numeric($data['images'])) {
                VenueImage::create(['school_id' => $venueInfo->school_id, 'venue_id' => $venueInfo->id, 'image_id' => $data['images']]);
            } else {
                $venueImages = [];
                foreach ($data['images'] as $_imgid) {
                    $venueImages[] = ['school_id' => $venueInfo->school_id, 'venue_id' => $venueInfo->id, 'image_id' => $_imgid];
                }
                (new VenueImage)->saveAll($venueImages);
            }

            Db::commit(); // 提交事务
        } catch (\Exception $e) {
            Db::rollback(); // 回滚事务
            trace('添加场地失败:'.$e->getMessage(), 'error');
            throw new \Exception('添加场地失败');
        }
    }

    /**
     * 编辑记录
     */
    public function updateItem($id, $data)
    {
        $venueInfo = self::where(['id' => $id, 'school_id' => app()->user->schoolid])->find();
        if (empty($venueInfo))  throw new \Exception('场地不存在');

        if (isset($data['bopen'])) {
            $data['bopen'] == 0 ? ($venueInfo->option |= 1) : ($venueInfo->option &= ~1);
        }
        if (isset($data['binside'])) {
            $data['binside'] == 0 ? ($venueInfo->option |= 2) : ($venueInfo->option &= ~2);
        }
        if (isset($data['opentime'])) {
            $venueInfo->open_time = self::parseOpentime($data['opentime']);
            if ($venueInfo->open_time <= 0) throw new \Exception('无效的开放时间');

            $venueInfo->max_continuous = $this->calculateMaxtime($venueInfo->open_time);
        }
        if (isset($data['limit_ordertime'])) {
            $venueInfo->limit_ordertime = $data['limit_ordertime'];
        }

        // 场地设备校验
        if (isset($data['facility'])) {
            $venueFacilitys = VenueFacility::where(['venue_id' => $venueInfo->id, 'status' => self::STATUS_NORMAL])->select();
            foreach ($venueFacilitys as $_venue) {
                $delFacility[$_venue->id] = $_venue;
            }

            foreach ($data['facility'] as $_facility) {
                $findObject = null;
                if (isset($_facility['id'])) {
                    if (!isset($delFacility[$_facility['id']]))    throw new \Exception('提交的场地数据不正确');

                    $findObject = $updateFacility[$_facility['id']] = $delFacility[$_facility['id']];
                    unset($delFacility[$_facility['id']]);
                } else {
                    $findObject = $addFacility[] = new VenueFacility;
                    $findObject->setAttr('type', $venueInfo->getAttr('type'));
                    $findObject->venue_id = $venueInfo->id;
                }

                $findObject->open_time = $venueInfo->open_time;
                $findObject->max_continuous = $venueInfo->max_continuous;

                $findObject->title = $_facility['title'];
                if (isset($_facility['bopen'])) {
                    $_facility['bopen'] == 0 ? ($findObject->option |= 1) : ($findObject->option &= ~1);
                }
                if (isset($_facility['binside'])) {
                    $_facility['binside'] == 0 ? ($findObject->option |= 2) : ($findObject->option &= ~2);
                }
                if (isset($_facility['opentime'])) {
                    $opentime = self::parseOpentime($_facility['opentime']);
                    if ($opentime > 0) {
                        $findObject->open_time = $opentime;
                        $findObject->max_continuous = $this->calculateMaxtime($opentime);
                    }
                }
                if (isset($_facility['limit_ordertime'])) {
                    $findObject->limit_ordertime = $_facility['limit_ordertime'];
                }
            }
        }

        // 场地图片校验
        if (isset($data['images'])) {
            $venueImage = VenueFile::where(['id' => $data['images']])->select();
            if ($venueImage->isEmpty()) throw new \Exception('不存在的场地图片');

            is_numeric($data['images']) && $data['images'] = [intval($data['images'])];
            foreach ($data['images'] as $_imgid) {
                $addImages[$_imgid] = ['school_id' => $venueInfo->school_id, 'venue_id' => $venueInfo->id, 'image_id' => $_imgid];
            }

            $images = VenueImage::where(['venue_id' => $venueInfo->id])->select();
            foreach ($images as $_img) {
                if (isset($addImages[$_img->image_id])) {
                    unset($addImages[$_img->image_id]);
                } else {
                    $delImages[] = $_img->id;
                }
            }
        }

        Db::startTrans();
        try {
            // 更新场地记录
            $venueInfo->updated_by = app()->user->id ?? 0;
            $venueInfo->save();

            // 删除不需要的设备
            (isset($delFacility) && !empty($delFacility)) && VenueFacility::update(['status' => self::STATUS_DELETE], ['id' => array_keys($delFacility)]);
            // 更新保留的设备
            if (isset($updateFacility)) {
                foreach ($updateFacility as $_upFacility) {
                    $_upFacility->updated_by = app()->user->id ?? 0;
                    $_upFacility->save();
                }
            }
            // 添加新的设备
            if (isset($addFacility)) {
                foreach ($addFacility as $_addFacility) {
                    $_addFacility->status = self::STATUS_NORMAL;
                    $_addFacility->school_id = app()->user->schoolid ?? 0;
                    $_addFacility->created_by = app()->user->id ?? 0;
                    $_addFacility->updated_by = app()->user->id ?? 0;

                    $find = VenueFacility::where(['school_id' => app()->user->schoolid, 'title' => $_addFacility->title])->find();
                    $find ? VenueFacility::update($_addFacility->getData(), ['id' => $find->id]) : $_addFacility->save();
                }
            }

            // 删除不需要的场地图片
            isset($delImages) && VenueImage::destroy($delImages);
            // 添加新的场地图片
            (isset($addImages) && empty($addImages)) || (new VenueImage)->saveAll(array_values($addImages));

            Db::commit(); // 提交事务
        } catch (\Exception $e) {
             Db::rollback(); // 回滚事务
            trace('添加场地失败:'.$e->getMessage(), 'error');
            throw new \Exception('添加场地失败');
        }
    }

    /**
     * 删除记录
     */
    public function delItem($id)
    {
        $find = self::where(['id' => $id, 'school_id' => app()->user->schoolid])->find();
        if ($find) {
            Db::startTrans();
            try {
                // 删除场地设备记录
                VenueFacility::update(['status' => self::STATUS_DELETE], ['venue_id' => $find->id]);

                // 删除场地图片
                VenueImage::where(['venue_id' => $find->id])->delete();

                self::update(['status' => self::STATUS_DELETE], ['id' => $find->id]);

                Db::commit(); // 提交事务
            } catch (\Exception $e) {
                Db::rollback(); // 回滚事务
                trace('删除场地失败:'.$e->getMessage(), 'error');
                throw new \Exception('删除场地失败');
            }
        }
    }

    /**
     * 读取场地类型名称
     */
    public function getTypeinfo()
    {
        return VenueType::find($this->getAttr('type'));
    }

    /**
     * 读取场地设备
     */
    public function getFacility()
    {
        return VenueFacility::where(['venue_id' => $this->id, 'status' => self::STATUS_NORMAL])->select();
    }

    /**
     * 读取场地设备数量
     */
    public function getFacilityCount()
    {
        return VenueFacility::where(['venue_id' => $this->id, 'status' => self::STATUS_NORMAL])->count();
    }

    /**
     * 读取场地封面图片
     */
    public function getImage()
    {
        return VenueFile::alias('vf')
            ->field('vf.id,vf.name,vf.url,vf.path,vf.ext,vf.mime_type,vf.size,vf.width,vf.height')
            ->join(VenueImage::getTable().' vi', 'vi.image_id=vf.id')
            ->where('vi.venue_id', $this->id)
            ->select();
    }

    /**
     * 格式化场地开放时间
     */
    public function getOpentime()
    {
        $bitCounts = 0;
        $timeRange = [];
        for ($i = 0; $i < 48; ++$i) {
            $bopen = $this->open_time & (1<<$i);
            $bopen && $bitCounts++;
            ($bopen && !(count($timeRange)%2)) && $timeRange[] = date('H:i', strtotime('0:0:0')+$i*1800);
            (!$bopen && (count($timeRange)%2)) && $timeRange[] = date('H:i', strtotime('0:0:0')+$i*1800);
        }

        $ranges = [];
        $timeRange = array_chunk($timeRange, 2);
        foreach ($timeRange as $_range) {
            $ranges[] = $_range[0].'~'.$_range[1];
        }

        return ['counts' => round($bitCounts/2, 1), 'ranges' => $ranges];
    }

    /**
     * 解析开放时间段
     * @param 开放时间段 $timeRange = [
            ['stime' => 1578967200, 'etime' => 1578974400],
            ['stime' => 1578981600, 'etime' => 1578992400],
            ['stime' => 1578999600, 'etime' => 1579006800]
        ];
     */
    public static function parseOpentime($timeRange)
    {
        $opentime = 0;
        foreach ($timeRange as $_range) {
            if (!isset($_range['stime']) || !isset($_range['etime']))   continue;
            
            $stime = strtotime(date('Ymd H:i:0', $_range['stime'])) - strtotime(date('Ymd 0:0:0', $_range['stime']));
            $etime = strtotime(date('Ymd H:i:0', $_range['etime'])) - strtotime(date('Ymd 0:0:0', $_range['etime']));
            while ($stime < $etime) {
                $opentime |= 1<<(intval($stime / 1800));
                
                $stime += 1800;
            }
        }

        return $opentime;
    }

    /**
     * 根据开放时间计算最大连续可预约时长
     */
    public function calculateMaxtime($opentime)
    {
        $opentime = intval($opentime);
        $bitCount = $opentime > 0 ? 1 : 0;
        while (($opentime &= $opentime>>1) > 0) { ++$bitCount;}

        return $bitCount;
    }
}
