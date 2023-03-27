<?php

namespace app\api\model;

use think\Model;

class Coupon extends Model
{
    public static function getCou()
    {
        $s = db('setting')->where('id', 1)->find();
        $zs_id  = $s['coupon_id'];
        return Coupon::where('end_time', '>', time())->where('id','not in',$zs_id)->select();
    }
}
