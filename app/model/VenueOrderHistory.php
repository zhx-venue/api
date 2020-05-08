<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueOrderHistory extends BaseModel
{
    /**
     * 审核操作类型
     */
    const OPTYPE_CHECKING = 1;
    /**
     * 签到操作类型
     * postion代表的含义：
     * bit0:签到是否迟到;
     */
    const OPTYPE_SIGNING = 2;
    /**
     * 签退操作类型
     * postion代表的含义：
     * bit0:签到是否早退;
     */
    const OPTYPE_SIGNOUTING = 3;
    /**
     * 退订操作类型
     */
    const OPTYPE_REVOKED = 4;
    /**
     * 拒绝操作类型
     */
    const OPTYPE_REFUSED = 5;
    /**
     * 取消操作类型
     */
    const OPTYPE_CANCEL = 6;
}
