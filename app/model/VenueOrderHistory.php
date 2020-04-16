<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueOrderHistory extends BaseModel
{
    const OPTYPE_CHECKING = 1; // 审核操作类型
    const OPTYPE_SIGNING = 2; // 签到操作类型
    const OPTYPE_SIGNOUTING = 3; // 签退操作类型
    const OPTYPE_REVOKED = 4; // 退订操作类型
    const OPTYPE_SIGNOUTED = 5; // 签退操作类型
    const OPTYPE_REFUSED = 6; // 拒绝操作类型
    const OPTYPE_CANCEL = 7; // 取消操作类型
}
