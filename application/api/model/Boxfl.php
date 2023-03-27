<?php

namespace app\api\model;

use think\Model;

class Boxfl extends Model
{
    public static function getBoxfl()
    {
        return Boxfl::where('boxswitch', 1)->order('sort desc')->select();
    }
}
