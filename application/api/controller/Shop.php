<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use app\api\model\Activity;
use app\api\model\Coupon;
use PDO;
use wxpay\wxpay;
use alipay\Alipay;
use epay\epay;

class Shop extends Api
{
    protected $noNeedLogin = ['getShopfl', 'getShopsy', 'getShop', 'getBanner', 'getShoplist', 'getShops', 'notifyx'];
    protected $noNeedRight = ['*'];
    /**
     * 获取商城分类
     */
    public function getShopfl()
    {
        $fl = Db::table('box_goodcategory')->where('categoryswitch', 1)->order('weigh DESC')->select();
        if (empty($fl)) {
            $this->error('未找到任何分类');
        }
        //将一级分类拿出来
        $first = [];
        foreach ($fl as $fl_v) {
            if ($fl_v['pid'] == 0) {
                $first[] = $fl_v;
            }
        }
        foreach ($fl as $fl_a) {
            foreach ($first as &$first_v) {
                if ($first_v['id'] == $fl_a['pid']) {
                    $first_v['foods'][] = $fl_a;
                }
            }
        }
        $this->success('分类数据', $first);
    }
    /**
     * 查询首页分类
     */
    public function getShopsy()
    {
        $fl = Db::table('box_goodcategory')->where('categoryswitch', 1)->where('pid', '<>', '0')->order('weigh DESC')->select();
        if (empty($fl)) {
            $this->error('未找到任何分类');
        }
        foreach ($fl as &$fl_v) {
            $fl_v['image'] = cdnurl($fl_v['image'], true);
        }
        $this->success('查询成功', $fl);
    }
    /*
    * 商品详情
    */
    public function getShop()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $shop = Db::table('box_goods')->where('id', input('sid'))->find();
        // $shop['good_images'] = 
        $image = explode(',', $shop['good_images']);
        foreach ($image as $v) {
            $shop['image'][] = cdnurl($v, true);
            $shop['stock'] = 999;
        }
        $shop['c_pirce'] = round($shop['c_pirce'] * $s['zhbl'],2);
        $this->success('查询成功', $shop);
    }
    /**
     * 商城顶部分类
     * 43367628@qq.com
     */
    public function getBanner()
    {
        $banner = Db::table('box_banner')->where('bswitch', 1)->order('weigh DESC')->select();
        if (empty($banner)) {
            $this->error('未找到轮播图');
        }
        foreach ($banner as &$banner_v) {
            $banner_v['image'] = cdnurl($banner_v['image'], true);
        }
        $this->success('轮播图数据', $banner);
    }
    /**
     * 获取商城首页商品
     */
    public function getShoplist()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        if(input('goods_tj2')!='id' &&input('goods_tj2')!=''){
            $order_px = input('goods_tj2').' '.input('goods_tj');
            $shops = Db::table('box_goods')->page(input('p', 1), 10)->order($order_px)->select();
        }else{
            $shops = Db::table('box_goods')->page(input('p', 1), 10)->order('sort desc,id asc')->select();
        }
        if (empty($shops)) {
            $this->error('未找到商品');
        }
        foreach ($shops as &$shop) {
            $image = explode(',', $shop['good_images']);

            foreach ($image as $i) {
                $shop['images'][] = cdnurl($i, true);
            }
            $shop['c_pirce'] = round($shop['c_pirce'] * $s['zhbl'],2);
        }
        // print_r($shops);
        $this->success('商品数据', $shops);
    }


    /**
     * 获取商城分类商品
     */
    public function getShops()
    {
        // $where = [];
        if (input('order') == 0) {
            $shops = Db::table('box_goods')->page(input('p', 1), 10)->where('goodcategory_id', input('fid'))->select();
        } else if (input('order') == 1) {
            if (input('type') == true) {
                $shops = Db::table('box_goods')->page(input('p', 1), 10)->where('goodcategory_id', input('fid'))->order('pirce ASC')->select();
            } else if (input('type') == false) {
                $shops = Db::table('box_goods')->page(input('p', 1), 10)->where('goodcategory_id', input('fid'))->order('pirce DESC')->select();
            }
        } else if (input('order') == 2) {
            if (input('type') == true) {
                $shops = Db::table('box_goods')->page(input('p', 1), 10)->where('goodcategory_id', input('fid'))->order('c_pirce ASC')->select();
            } else if (input('type') == false) {
                $shops = Db::table('box_goods')->page(input('p', 1), 10)->where('goodcategory_id', input('fid'))->order('c_pirce DESC')->select();
            }
        }
        if (empty($shops)) {
            $this->error('未找到商品');
        }
        foreach ($shops as &$shop) {
            $image = explode(',', $shop['good_images']);
            // $shop['good_images'] = cdnurl($shop['good_images'], true);
            // $shop['xh'] = explode(',', $shop['tag']);
            foreach ($image as $i) {
                $shop['images'][] = cdnurl($i, true);
            }
        }
        // print_r($shops);
        $this->success('商品数据', $shops);
    }

    /**
     * 商城支付
     */
    public function goPay()
    {
        //创建订单
        $post = input('post.');
        $ooid = 'ALDMH' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $epay = new epay;
        if (empty($post['addresid'])) {
            $this->error('未选择收获地址');
        }
        $shop = Db::table('box_goods')->where('id', $post['shopid'])->find();
        $addres = Db::table('box_user_address')->where('id', $post['addresid'])->find();
        // $user = Db::table('box_user')->where('id',$this->auth)
        $order = [
            'shop_id' => $post['shopid'],
            'shop_name' => $shop['goods_name'],
            'image' => $shop['good_images'],
            'num' => $post['num'],
            'user_id' => $this->auth->id,
            'pay_method' => $post['payfs'], //支付方式:wechat=微信,alipay=支付宝,lucyk=幸运币
            'out_trade_no' => $ooid,
            'status' => 'unpay', //状态:unpay=待支付,used=待发货,refund=待收货,ywc=已完成,undei=已关闭
            'create_time' => time(),
            'address' => $addres['province'] . $addres['city'] . $addres['area'] . $addres['detail'],
            'username' => $addres['name'],
            'mobile' => $addres['mobile'],
            'delivery_fee' => $shop['freight'],
            'terminal' => $post['terminal'],
            'desc' => $post['desc'],
            'create_time'=>time(),
        ];
        if ($post['payfs'] == 'wechat') {
            $order['price'] = $shop['pirce'];
            $order['pay_rmb'] = $shop['pirce'] * $post['num'];
            $price = ($shop['pirce'] * $post['num']) + $shop['freight'];
            if ($post['terminal'] == 0) {
                //如果为0就是H5
                $ret = Db::table('box_shoporder')->insert($order);
                if (empty($ret)) {
                    $this->error('创建订单失败');
                }
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epayscnotifyx';
                $epay->goePay($ooid, 'wechat', '购买商品', $price, $notifyurl, $this->request->domain() . '/h5/#/pages/mall/paySuccexx');
            } else if ($post['terminal'] == 1) {
                $ret = Db::table('box_shoporder')->insert($order);
                if (empty($ret)) {
                    $this->error('创建订单失败');
                }
                // 如果为1就是小程序
                $this->payJoinfee('购买商品', $ooid, $price);
            } else if ($post['terminal'] == 2) {
                //如果为2就是APP
            }
            // $this->payJoinfee('抽取盲盒', $ooid, $post['price']);
        } else if ($post['payfs'] == 'lucyk') {
            //否则就是金币支付
            $s = Db::table('box_setting')->where('id', 1)->find();
            $shop['c_pirce'] = $shop['c_pirce'] * $s['zhbl'];
            $price = $shop['c_pirce'] * $post['num'] + $shop['freight'];
            $order['price'] = $shop['c_pirce'];
            $order['pay_coin'] = $price;
            if ($this->auth->score < $price) {
                $this->error('幸运币不足');
            }
            $ret = Db::table('box_shoporder')->insert($order);
            if (empty($ret)) {
                $this->error('创建订单失败');
            }
            // $order = Db::table('box_shoporder')->where('out_trade_no', $ooid)->find();
            //更新用户幸运币余额
            //记录幸运币表
            $coindata = [
                'user_id' => $this->auth->id,
                'before' => $this->auth->score,
                'after' => $this->auth->score - $price,
                'coin' => $price,
                'type' => 'pay_shop',
                'money_type'=>0,
                'order_id' => null,
                'create_time' => time()
            ];
            Db::table('box_coin_record')->insert($coindata);
            $res = Db::table('box_user')->where('id', $this->auth->id)->update(['score' => ($this->auth->score - $price)]);
            if (empty($res)) {
                $this->error('兑换失败');
            }

            //支付成功更改订单状态为待发货
            Db::table('box_shoporder')->where('out_trade_no', $ooid)->update(['status' => 'used']);
            $this->success('兑换成功');
        } else if ($post['payfs'] == 'xiguazi') { //幸运籽支付
            //否则就是金币支付
            $price = $shop['xgz_price'] * $post['num'] + $shop['freight'];
            $order['price'] = $shop['xgz_price'];
            $order['pay_money'] = $price;
            if ($this->auth->money < $price) {
                $this->error('钻石不足');
            }
            $ret = Db::table('box_shoporder')->insert($order);
            if (empty($ret)) {
                $this->error('创建订单失败');
            }
            // $order = Db::table('box_shoporder')->where('out_trade_no', $ooid)->find();
            //更新用户幸运币余额
            //记录幸运币表
            $coindata = [
                'user_id' => $this->auth->id,
                'before' => $this->auth->money,
                'after' => $this->auth->money - $price,
                'coin' => $price,
                'type' => 'pay_shop',
                'money_type'=>1,
                'order_id' => null,
                'create_time' => time()
            ];
            Db::table('box_coin_record')->insert($coindata);
            $res = Db::table('box_user')->where('id', $this->auth->id)->update(['money' => ($this->auth->money - $price)]);
            if (empty($res)) {
                $this->error('兑换失败');
            }
            //支付成功更改订单状态为待发货
            Db::table('box_shoporder')->where('out_trade_no', $ooid)->update(['status' => 'used']);
            $this->success('兑换成功');
            
        } else if ($post['payfs'] == 'alipay') {
            $order['price'] = $shop['pirce'];
            $order['pay_rmb'] = $shop['pirce'] * $post['num'];
            $price = ($shop['pirce'] * $post['num']) + $shop['freight'];
            if ($post['terminal'] == 0) {
                         $ret = Db::table('box_shoporder')->insert($order);
                if (empty($ret)) {
                    $this->error('创建订单失败');
                }
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epayscnotifyx';
                $epay->goePay($ooid, 'alipay', '购买商品', $price, $notifyurl, $this->request->domain() . '/h5/#/pages/mall/paySuccexx');
            } else if ($post['terminal'] == 1) {
                $ret = Db::table('box_shoporder')->insert($order);
                if (empty($ret)) {
                    $this->error('创建订单失败');
                }
                // 如果为1就是小程序
                $this->payJoinfee('购买商品', $ooid, $price);
            } else if ($post['terminal'] == 2) {
                //如果为2就是APP
                Db::table('box_shoporder')->insert($order);
                $alipay = new Alipay();
                $notice = $this->request->domain() . '/index.php/api/pay/shopnotifyx';
                $alipay->pay('购买商品', $ooid, $price, $notice);
            }
        }
    }
    //支付费用
    public function payJoinfee($name, $ooid, $price)
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $appid = $s['mpappid'];
        $openid = $this->auth->wx_mini_openid;
        $mch_id = $s['payid'];
        $key = $s['paykey'];
        import('Weixin.Lib.WeixinPay');
        $notifyurl = $this->request->domain() . '/index.php/api/shop/notifyx';
        $weixinpay = new wxpay($appid, $openid, $mch_id, $key, $name, $ooid, $price, $notifyurl);
        $return = $weixinpay->pay();
        $this->success('支付', ['data' => $return, 'ooid' => $ooid]);
    }
    public function notifyx()
    {
        $testxml = file_get_contents("php://input");
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true); //转成数组，
        if ($result) {
            //如果成功返回了
            $out_trade_no = $result['out_trade_no'];
            $transaction_id = $result['transaction_id'];
            $xml = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            echo $xml;
            $this->handle($out_trade_no, $transaction_id);
        }

    }
    //订单处理
    public function handle($ooid, $tranid)
    {
        $ret = Db::table('box_shoporder')->where('out_trade_no', $ooid)->update(['status' => 'used', 'pay_time' => time(), 'transaction_id' => $tranid]);
        $order = Db::table('box_shoporder')->where('out_trade_no', $ooid)->find();
        $user = Db::table('box_user')->where('id', $order['user_id'])->find();
        //更新用户幸运币余额
        $res = Db::table('box_user')->where('id', $order['user_id'])->update(['score' => $user['score'] - $order['pay_coin']]);
        Db::table('box_coin_record')->insert([
            'user_id' => $user['id'],
            'before' => $user['score'],
            'after' => $user['score'] - $order['pay_coin'],
            'coin' => $order['pay_coin'],
            'type' => 'pay_shop',
            'order_id' => $order['id'],
            'create_time' => time()
        ]);
    }
    /**
     * 待支付订单付款
     */
    public function dzfOrder()
    {
        $ooid = 'ALDMH' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $order = Db::table('box_shoporder')->where('out_trade_no', input('ooid'))->find();
        if (empty($order)) {
            $this->error('订单出错了');
        }
        Db::table('box_shoporder')->where('out_trade_no', input('ooid'))->update(['out_trade_no'=>$ooid]);
        if ($order['terminal'] == 0) {
            //如果为0就是H5
            $epay = new epay;
            if ($order['pay_method'] == 'alipay') {
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epayscnotifyx';
                $epay->goePay($ooid, 'alipay', '购买商品', $order['pay_rmb'], $notifyurl, $this->request->domain() . '/h5/#/pages/mall/paySuccexx');
            } else if ($order['pay_method'] == 'wechat') {
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epayscnotifyx';
                $epay->goePay($ooid, 'wechat', '购买商品', $order['pay_rmb'], $notifyurl, $this->request->domain() . '/h5/#/pages/mall/paySuccexx');
            }
        } else if ($order['terminal'] == 1) {
            $this->payJoinfee('购买商品', $ooid, $order['pay_rmb']);
        } else if ($order['terminal'] == 2) {
            $alipay = new Alipay();
            $notice = $this->request->domain() . '/index.php/api/pay/shopnotifyx';
            $alipay->pay('购买商品', $ooid, $order['pay_rmb'], $notice);
        }
    }
}
