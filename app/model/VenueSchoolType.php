<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueSchoolType extends BaseModel
{
    /**
     * 关联类型表
     */
    public function types()
    {
        return $this->belongsTo(VenueType::class, 'type_id');
    }

    /**
     * 读取学校场地类型列表
     */
    public static function list($filter)
    {
        return self::alias('vst')
            ->field('vt.id,vt.title,vt.position,vt.sort,vst.created_at,vst.created_by')
            ->join(VenueType::getTable().' vt', 'vt.id=vst.type_id')
            ->where(['vst.school_id' => app()->user->schoolid, 'vst.status' => self::STATUS_NORMAL])
            ->select();
    }

    /**
     * 初始化学校默认的场地类型
     * @param $schoolId 学校记录ID
     */
    public static function initData($schoolId)
    {
        // 读取默认的类型
        $defaultTypes = VenueType::where('position', 'exp', '&1')->where('status', self::STATUS_NORMAL)->select();
        foreach ($defaultTypes as $_type) {
            $find = self::where(['school_id' => $schoolId, 'type_id' => $_type->id])->find();
            if (!$find) {
                self::create(['school_id' => $schoolId, 'type_id' => $_type->id]);
            } elseif ($find->status != self::STATUS_NORMAL) {
                $find->save(['status' => self::STATUS_NORMAL]);
            }
        }
    }

    /**
     * 添加场地类型
     */
    public static function addType($data)
    {
        $find = VenueType::where('title', $data['title'])->find();
        if (!$find) {
            $find = VenueType::create($data);
        }

        $stype = self::where(['school_id' => app()->user->schoolid, 'type_id' => $find->id])->find();
        if (!$stype || $stype->status != self::STATUS_NORMAL) {
            $newData = [
                'type_id' => $find->id, 
                'school_id' => app()->user->schoolid, 
                'status' => self::STATUS_NORMAL
            ];
            self::create($newData, [], true);
        }
    }

    /**
     * 删除场地类型
     */
    public static function delType($id)
    {
        $find = self::where(['school_id' => app()->user->schoolid, 'type_id' => $id])->find();
        $find && $find->delete();
    }
}
