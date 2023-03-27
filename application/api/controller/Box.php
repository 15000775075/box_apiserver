<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Sms as Smslib;
use app\common\model\User;
use think\Hook;
use think\Db;
use app\api\model\Boxfl;

class Box extends Api
{
    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = ['*'];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 权限Auth
     * @var Auth 
     */
    protected $auth = null;

    /**
     * 获取盲盒信息
     */
    public function getBox()
    {
        $box = Boxfl::getBoxfl();
        $order = Db::table('box_order')->where('user_id',$this->auth->id)->where('status','used')->select();
        $s = Db::name('setting')->where('id',1)->find();
        if (empty($box)) {
            $this->error('未找到盲盒');
        } else {
            foreach ($box as &$box_v) {
                $box_v['box_banner_images'] = cdnurl($box_v['box_banner_images'], true);
                $box_v['box_foot_images'] = cdnurl($box_v['box_foot_images'], true);
                $box_v['score_rmb_rate'] = $s['score_rmb_rate'];
                if($box_v['xryh'] == 1){
                    //等于1代表开启新人优惠;
                    if(empty($order)){
                        //为空则开启新人优惠
                        $box_v['yh'] = 1;
                    }else{
                        $box_v['yh'] = 0;
                    }
                }
            }
            
            $this->success('盲盒数据', $box);
        }
    }
    /**
     * 查询盲盒商品
     */
    public function getBoxShop()
    {
        //获取盲盒ID
        $id = input('id');
        //         print_r($id);
        // exit;
        //根据ID查询对应盲盒
        $boxfl = Db::table('box_boxfl')->where('boxswitch', 1)->where('id', $id)->find();
        $s = Db::name('setting')->where('id',1)->find();

        //查询对应商品
        $shops = Db::table('box_mhgoods')->where('boxfl_id', $id)->select();
        // print_r($shops);
        $boxfl['box_banner_images'] = cdnurl($boxfl['box_banner_images'], true);
        $boxfl['box_foot_images'] = cdnurl($boxfl['box_foot_images'], true);
        $boxfl['score_rmb_rate'] = $s['score_rmb_rate'];
        $s = Db::table('box_setting')->where('id',1)->find();

        foreach ($shops as &$shop) {
            $shop['images'] = explode(',', $shop['goods_images']);
            $shop['image'] = cdnurl($shop['images'][0], true);
            $shop['luckycoin'] = ($shop['goods_pirce'] * ($s['dhbl']/100));
             
            
        }
        $znum = Db::table('box_mhgoods')->where('boxfl_id', $id)->count();
        $ssnum = Db::table('box_mhgoods')->where('boxfl_id', $id)->where('tag', 'rare')->count();
        $zgprice = Db::table('box_mhgoods')->where('boxfl_id', $id)->order('goods_pirce DESC')->select();
        $zdprice = Db::table('box_mhgoods')->where('boxfl_id', $id)->order('goods_pirce ASC')->select();
        $this->success('查询成功', ['mh' => $boxfl, 'shop' => $shops, 'znum' => $znum, 'xynum' => $ssnum, 'zg' => $zgprice[0]['goods_pirce'], 'zd' => $zdprice[0]['goods_pirce']]);
    }
    /**
     * 查看每个商品详情
     */
    public function getShop()
    {
        //获取商品ID
        $id = input('id');
        $s = Db::table('box_setting')->where('id', 1)->find();
        $shop = Db::table('box_mhgoods')->where('id', $id)->find();
              $s = Db::table('box_setting')->where('id',1)->find();
        if (empty($shop)) {
            $this->error('未找到该商品');
        }
        $image = explode(',', $shop['goods_images']);
        // $shop['image'] = cdnurl($image[0], true);
        foreach ($image as &$image_v) {
            $shop['image'][] = cdnurl($image_v, true);
            $shop['luckycoin'] = ($shop['goods_pirce'] * ($s['dhbl']/100));
        }
        $shop['luckycoin'] = round($shop['luckycoin'] * $s['zhbl'],2);
        $this->success('查询成功', $shop);
    }
    /**
     * 获取连抽优惠
     */
    public function getlcyh()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $this->success('优惠比例', $s['lcyhbl'] / 100);
    }
    /*
    获取盲盒商品
    */
    public function getBoxs()
    {
        //获取盲盒ID
        $id = input('id');
        //查询对应商品
        $shops = Db::table('box_mhgoods')->where('boxfl_id', $id)->order('goods_pirce DESC')->select();
        // print_r($shops);
        $cs = [];
        $ss = [];
        $xy = [];
        $gj = [];

        foreach ($shops as &$shop) {
            $shop['images'] = explode(',', $shop['goods_images']);
            // $shop['goods_image'] = cdnurl($shop['goods_image'], true);
            $shop['image'] = cdnurl($shop['images'][0], true);
        }
        foreach ($shops as $shop_v) {
            if ($shop_v['tag'] == 'normal') {
                $gj[] = $shop_v;
            } else if ($shop_v['tag'] == 'rare') {
                $xy[] = $shop_v;
            } else if ($shop_v['tag'] == 'supreme') {
                $ss[] = $shop_v;
            } else if ($shop_v['tag'] == 'legend') {
                $cs[] = $shop_v;
            }
        }
        $arr = [
            'gj' => $gj,
            'xy' => $xy,
            'ss' => $ss,
            'cs' => $cs,
        ];
        $this->success('商品', $arr);
    }
}
