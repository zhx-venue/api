<?php
// 应用公共文件

use app\model\User;
use app\model\VenueRole;

/**
 * 权限检测
 * @param int $module 模块
 * @param int $position 模块操作
 * @return true or false
 */
function checkAuth($module, $position=null)
{
    $auths = [];
    if (app()->user->isGuest()) {
        $auths = VenueRole::getGuestAuth();
    } elseif (app()->user->type == User::TYPE_VISITOR) {
        $auths = VenueRole::getVisitorAuth();
    } else {
        $auths = VenueRole::getUserAuth();
    }

    return VenueRole::checkAuth($module, $position, $auths);
}

/**
 * 解析预约的时间
 * @param $datetime 预约的日期
 * @param $opentime 预约的时间
 * @return array [$stime, $etime]
 */
function parse_ordertime($datetime, $opentime)
{
    $stime = $etime = 0;
    for ($i = 0; $i < 48; ++$i) {
        $bopen = $opentime & (1<<$i);
        $bopen && $stime <= 0 && $stime = $datetime+$i*1800;
        !$bopen && $stime > 0 && $etime <= 0 && $etime = $datetime+$i*1800;
    }

    return [$stime, $etime];
}

/**
 * 格式化开放时间
 * @param $opentime 开放时间
 * @param $date 开放时间所在的日期 时间戳
 */
function format_opentime($opentime, $date=null)
{
    $date = is_null($date) ? strtotime('0:0:0') : intval($date);

    $bitCounts = 0;
    $openHours = $rangeHour = $rangeDate = [];
    for ($i = 0; $i < 48; ++$i) {
        $bopen = $opentime & (1<<$i);
        if ($bopen) {
            $bitCounts++;
            $openHours[] = [$date+$i*1800, date('H:i', $date+$i*1800)];
        }

        if ( ($bopen && !(count($rangeHour)%2)) || (!$bopen && (count($rangeHour)%2)) ) {
            $rangeHour[] = [$date+$i*1800, date('H:i', $date+$i*1800)];
        }
        if ( ($bopen && !(count($rangeDate)%2)) || (!$bopen && (count($rangeDate)%2)) ) {
            $rangeDate[] = [$date+$i*1800, date('Y年m月d日 H:i', $date+$i*1800)];
        }
    }

    $rangeHour = array_chunk($rangeHour, 2);
    foreach ($rangeHour as $_range) {
        $rangeHours[] = ['stime' => $_range[0], 'etime' => $_range[1]];
    }
    $rangeDate = array_chunk($rangeDate, 2);
    foreach ($rangeDate as $_range) {
        $rangeDates[] = ['stime' => $_range[0], 'etime' => $_range[1]];
    }

    return ['counts' => round($bitCounts/2, 1), 'openHours' => $openHours, 'rangeHours' => $rangeHours ?? [], 'rangeDates' => $rangeDates ?? []];
}