<?php

namespace app\api\model;

use think\Model;

class Activity extends Model
{
    public static function getHd()
    {
        return Activity::select();
    }
}
