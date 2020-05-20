<?php
declare (strict_types = 1);

namespace app\model;

use app\BaseModel;

/**
 * @mixin think\Model
 */
class VenueVisitorBan extends BaseModel
{
    const BAN = 0;
    const NORMAL = 1;
}
