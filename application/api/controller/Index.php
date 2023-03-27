<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use app\api\model\Activity;
use app\api\model\Coupon;
use think\Request;
use wxpay\wxpay;
use alipay\Alipay;
use fast\Http;
use epay\epay;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['getSettingkf', 'getHd', 'getCoupon', 'getBoxlist', 'getSite', 'getSetting', 'getCards', 'notifyx', 'getApp', 'SgetOne', 'index','upload','getBanner'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
    }
    /*设置信息*/
    public function getSettingkf()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $s['kfimage'] = cdnurl($s['kfimage'], true);
        $this->success('查询成功', $s);
    }
    //支付费用
    public function payJoinfee($name, $ooid, $price)
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $appid = $s['mpappid'];
        $openid = $this->auth->wx_mini_openid;
        $mch_id = $s['payid'];
        $key = $s['paykey'];
        // import('Weixin.Lib.WeixinPay');
        $notifyurl = $this->request->domain() . '/index.php/api/index/notifyx';
        $weixinpay = new wxpay($appid, $openid, $mch_id, $key, $name, $ooid, $price, $notifyurl);
        $return = $weixinpay->pay();
        $this->success('支付', ['data' => $return, 'ooid' => $ooid]);
    }
    /**
     * 获取活动专区
     */
    public function getHd()
    {
        $hd =  Activity::getHd();
        if (empty($hd)) {
            $this->error('暂无活动');
        } else {
            foreach ($hd as &$hd_v) {
                $hd_v['activityimage_v'] = cdnurl($hd_v['activityimage'], true);
                $hd_v['tcimage'] = cdnurl($hd_v['tcimage'], true);
            }
            $this->success('查询成功', $hd);
        }
    }
    /**
     * 获取优惠券
     */
    public function getCoupon()
    {
        //获取全部优惠券
        $coupon = Coupon::getCou();
        if (empty($coupon)) {
            $this->error('暂无可领取优惠券');
        }
        //   print_r($this->auth->id);
        //   exit;
        //获取已经领取的优惠券
        $coupon_list = Db::table('box_coupon_list')->where('user_id', $this->auth->id)->select();
        // print_r($coupon_list);
        // exit;
        if (!empty($coupon_list)) {
            foreach ($coupon as &$a) {
                foreach ($coupon_list as $b) {
                    if ($b['coupon_id'] == $a['id']) {
                        $a['status_a'] = 1;
                    } else {
                        if (empty($a['status_a'])) {
                            $a['status_a'] = 0;
                        }
                    }
                }
            }
        } else {
            foreach ($coupon as &$c) {
                $c['status_a'] = 0;
            }
        }
        $this->success('优惠券列表', $coupon);
    }
    /**
     * 获取开箱记录
     */
    public function getBoxlist()
    {
        //查询所有盒柜商品
        $list = Db::table('box_mhlog')
        ->alias('log')
        ->join('box_mhgoods goods','log.mhgoods_id = goods.id')
        ->order('goods.goods_pirce DESC')->page(1, 10)->select();
        
        foreach ($list as &$item){
            $item['mhimage'] = cdnurl($item['mhimage'], true);
            $item['gl'] = $item['tag'];
        }
        $this->success('开箱记录', $list);
        
        // $id = [];
        // foreach ($list as $list_v) {
        //     $id[] = $list_v['mhgoods_id'];
        // }
        // $shops = Db::table('box_mhgoods')->where('id', 'in', $id)->select();
        // // print_r($shops);
        // // exit;
        // foreach ($list as &$list_a) {
        //     $list_a['mhimage'] = cdnurl($list_a['mhimage'], true);
        //     foreach ($shops as $shop) {
        //         if ($list_a['mhgoods_id'] == $shop['id']) {

        //             $list_a['gl'] = $shop['tag'];
        //         }
        //     }
        // }
        // $this->success('开箱记录', $list);
    }
    /**
     * 获取 新手教程
     */
    public function getSetting()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $s['kfimage'] = cdnurl($s['kfimage'], true);
        $arr = [
            'kfimg' => $s['kfimage'],
            'xsjc' => $s['tutorialfile']
        ];
        $this->success('查询成功', $arr);
    }
    /**
     * 开盲盒支付
     */
    public function goPay()
    {
        $post = input('post.');
        $ooid = 'ALDMH' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $box = Db::table('box_boxfl')->where('id', $post['boxid'])->find();
        $user = Db::table('box_user')->where('id',$this->auth->id)->find();
        if($user['money']<$post['xs']){
            $this->error('您的星石不足抵扣');
        }
        if(!empty($post['currentCoupon_id'])){
            $my_current = db('coupon_list')->where('id',$post['currentCoupon_id'])->where('user_id',$this->auth->id)->find();
            if(!$my_current  ||  $my_current['status']!=0){
                $this->error('优惠券已被使用');
            }
            db('coupon_list')->where('id',$post['currentCoupon_id'])->where('user_id',$this->auth->id)->update(['status'=>1]);
            
        }
        // print_r($post);
        // exit;
        $data = [
            'boxfl_id' => $post['boxid'],
            'boxfl_name' => $box['box_name'],
            'image' => $box['box_banner_images'],
            'num' => $post['num'],
            'user_id' => $this->auth->id,
            'pay_method' => $post['payfs'],
            'pay_coin' => $post['price'],
            'out_trade_no' => $ooid,
            'status' => 'unpay', //unpay=待支付,used=已使用,refund=已关闭
            'create_time' => time(),
            'terminal' => $post['terminal'],
            'xingshi' => $post['xs']
        ];
        $oid = Db::table('box_order')->insertGetId($data);
        if (!empty($oid)) {
            if (!empty($post['couid'])) {
                Db::table('box_coupon_list')->where('id', $post['couid'])->update(['status' => 1, 'sytime' => time()]);
            }
        }
        
        if($post['price'] == 0){
             $ret = Db::table('box_order')->where('id',$oid)->update(['status' => 'used', 'pay_time' => time()]);
             $s = Db::table('box_setting')->where('id',1)->find();
             if($post['xs']!=0){
                Db::table('box_user')->where('id', $this->auth->id)->update(['money' => $user['money'] - $post['xs']]);
                Db::table('box_moneylog')->insert([
                    'user_id' => $this->auth->id,
                    'beforemoney' => $user['money'],
                    'aftermoney' => $user['money'] - $post['xs'],
                    'money' => $post['xs'],
                    'bgexplain' => 'dikou',
                    'addtime' => time()
                ]);
             }
            if (!empty($user['pid'])) {
                //处理返佣
                $u = Db::table('box_user')->where('id', $user['pid'])->find();
                Db::table('box_user')->where('id', $user['pid'])->update(['score' => $u['score'] + 618]);
                $boxx = Db::table('box_boxfl')->where('id', $s['boxfl_id'])->find();
                $res = Db::table('box_yhbox')->insert([
                    'boxfl_id' => $s['boxfl_id'],
                    'user_id' => $user['pid'],
                    'status' => 1,
                    'addtime' => time()
                ]);
                if (!empty($res)) {
                    Db::table('box_detailed')->insert([
                        'user_id' => $user['pid'],
                        'lytag' => 'yaoqing',
                        'lxtag' => 'yhq',
                        'boxfl_id' => $s['boxfl_id'],
                        'laiyuan' => '邀请好友奖励' . $boxx['box_name'],
                        'jltime' => time()
                    ]);
                    Db::table('box_coin_record')->insert([
                        'user_id' => $u['id'],
                        'before' => $u['score'],
                        'after' => $u['score'] + 618,
                        'coin' => 618,
                        'type' => 'fxfy',
                        'create_time' => time()
                    ]);
                }
            }
            $this->success('支付成功',['ooid'=>$ooid]);
        }  
        
        /**
         * 判断来源
         * 0：H5
         * 1：小程序
         * 2：APP
         */
         if($post['payfs']=='score'){ //金豆支付
            $this->scorePay($ooid);
            die;
        }
        if ($post['terminal'] == 0) {
            //如果为0就是H5
            $epay = new epay;
            if ($post['payfs'] == 'alipay') {
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epaymhnotifyx';
                $epay->goePay($ooid, 'alipay', '抽取盲盒', $post['price'], $notifyurl, $this->request->domain() . '/h5/#/pages/home/luckbox?ooid=' . $ooid . '&num=' . $post['num'] . '&boxid=' . $post['boxid']);
            } else if ($post['payfs'] == 'wechat') {
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epaymhnotifyx';
                $epay->goePay($ooid, 'wechat', '抽取盲盒', $post['price'], $notifyurl, $this->request->domain() . '/h5/#/pages/home/luckbox?ooid=' . $ooid . '&num=' . $post['num'] . '&boxid=' . $post['boxid']);
            }
        } else if ($post['terminal'] == 1) {
            // 如果为1就是小程序
            $this->payJoinfee('抽取盲盒', $ooid, $post['price']);
        } else if ($post['terminal'] == 2) {
            //如果为2就是APP
            $alipay = new Alipay();
            $notice = $this->request->domain() . '/index.php/api/pay/mhnotifyx';
            $alipay->pay('抽取盲盒', $ooid, $post['price'], $notice);
        }
    }
    //幸运币余额支付订单
    function scorePay($ooid){
        $mh_order = Db::name('order')->where('out_trade_no',$ooid)->find();
        if(!$mh_order){
            $this->error('订单不存在');
        }
        $user = Db::name('user')->where('id',$this->auth->id)->find();
        if($user['score']<$mh_order['pay_coin']){
            $this->error('您的幸运币余额不足！');
        }
        //记录幸运币表
        $coindata = [
            'user_id' => $user['id'],
            'before' => $user['score'],
            'after' => $user['score'] - $mh_order['pay_coin'],
            'coin' => $mh_order['pay_coin'],
            'type' => 'pay_shop',
            'money_type'=>0,
            'order_id' => $mh_order['id'],
            'create_time' => time()
        ];
        $coinjl = Db::table('box_coin_record')->insert($coindata);
        $res = Db::name('user')->where('id', $this->auth->id)->setDec('score', $mh_order['pay_coin']);
        if($res){
            $PayController = new \app\api\controller\Pay();
            $PayController->handle($ooid,'jindou_pay-'.$ooid);//该控制器需要的参数
            $this->success('支付成功',['ooid'=>$ooid]);
        }else{
            $this->error('金豆扣除失败，请重试');
        }
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
            // $this->handle($out_trade_no, $transaction_id);
            $PayController = new \app\api\controller\Pay();
            $res = $PayController->handle($out_trade_no,$transaction_id);//该控制器需要的参数
        }
    }
    // //订单处理
    // function handle($ooid, $tranid)
    // {
    //     $ret = Db::table('box_order')->where('out_trade_no', $ooid)->update(['status' => 'used', 'pay_time' => time(), 'transaction_id' => $tranid]);
    //     $order = Db::table('box_order')->where('out_trade_no', $ooid)->find();
    //     $s = Db::table('box_setting')->where('id', 1)->find();
    //     $user = Db::table('box_user')->where('id', $order['user_id'])->find();
    //     if (!empty($ret)) {
    //         if (!empty($user['pid'])) {
    //             //处理普通返佣
    //             $u = Db::table('box_user')->where('id', $user['pid'])->find();
    //             if ($u['isdl'] == 1) {
    //                 //如果是代理
    //                 //金币兑换比例 1:100
    //                 $bl = $s['zhbl'];
    //                 $fyje = $order['pay_coin'] * ($u['sharebl'] / 100);
    //                 //获取的返佣幸运币金额
    //                 $coin = $fyje;
    //                 $usercoin = Db::table('box_user')->where('id', $u['id'])->update(['score' => $u['score'] + $coin]);
    //                 if (!empty($usercoin)) {
    //                     //记录幸运币表
    //                     $coindata = [
    //                         'user_id' => $u['id'],
    //                         'before' => $u['score'],
    //                         'after' => $u['score'] + $coin,
    //                         'coin' => $coin,
    //                         'type' => 'fxfy',
    //                         'order_id' => $order['id'],
    //                         'create_time' => time()
    //                     ];
    //                     $coinjl = Db::table('box_coin_record')->insert($coindata);
    //                     //记录返佣明细表
    //                     $fyjldata = [
    //                         'user_id' => $u['id'],
    //                         'lytag' => 'kaihe',
    //                         'lxtag' => 'coin',
    //                         'coinnum' => $coin,
    //                         'laiyuan' => $user['nickname'].'开盒返佣',
    //                         'jltime' => time()
    //                     ];
    //                     $fyjl = Db::table('box_detailed')->insert($fyjldata);
    //                 }
    //             } else {
    //                 $box = Db::table('box_boxfl')->where('id', $s['boxfl_id'])->find();
    //                 $res = Db::table('box_yhbox')->insert([
    //                     'boxfl_id' => $s['boxfl_id'],
    //                     'user_id' => $user['pid'],
    //                     'status' => 1,
    //                     'addtime' => time()
    //                 ]);
    //                 if (!empty($res)) {
    //                     Db::table('box_detailed')->insert([
    //                         'user_id' => $user['pid'],
    //                         'lytag' => 'yaoqing',
    //                         'lxtag' => 'box',
    //                         'boxfl_id' => $s['boxfl_id'],
    //                         'laiyuan' => $user['nickname'].'开盒奖励' . $box['box_name'],
    //                         'jltime' => time()
    //                     ]);
    //                 }
    //             }
    //         }
    //     }
    // }

    /**
     * 抽取盲盒
     * @param int $box_id 盲盒
     * @return mixed
     * @throws \Exception
     * @author 汇享 <43367628@qq.com>
     */
    public function getOne()
    {
        // echo '{"code":1,"msg":"抽奖结果","time":"1673008224","data":{"data":[{"id":101,"goods_name":"拍立得相机学生款 傻瓜相机","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/fb1e0e29e0508303e35816995cb988a9.jpg","goods_stock":null,"goods_pirce":99,"delivery_fee":"5.00","create_time":1669049895,"tag":"normal","luckycoin":"150.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/4d430d493b775de95d63cfc271eece2b.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/fb1e0e29e0508303e35816995cb988a9.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/fb1e0e29e0508303e35816995cb988a9.jpg"]},{"id":95,"goods_name":"松典（SONGDIAN） 数码学生相机便携CCD卡片机","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/10e599acd6fab90afaa29bc010738183.jpg","goods_stock":null,"goods_pirce":529,"delivery_fee":"10.00","create_time":1669049095,"tag":"rare","luckycoin":"888.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/61fc12fb455acb6ae03260922de61b5b.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/10e599acd6fab90afaa29bc010738183.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/10e599acd6fab90afaa29bc010738183.jpg"]},{"id":101,"goods_name":"拍立得相机学生款 傻瓜相机","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/fb1e0e29e0508303e35816995cb988a9.jpg","goods_stock":null,"goods_pirce":99,"delivery_fee":"5.00","create_time":1669049895,"tag":"normal","luckycoin":"150.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/4d430d493b775de95d63cfc271eece2b.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/fb1e0e29e0508303e35816995cb988a9.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/fb1e0e29e0508303e35816995cb988a9.jpg"]},{"id":99,"goods_name":"富士instax立拍立得 一次成像相机 mini7+","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/cbe75f66e6b4b4720b04ba9122657da1.jpg","goods_stock":null,"goods_pirce":399,"delivery_fee":"10.00","create_time":1669049736,"tag":"rare","luckycoin":"444.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/e075bc5a5159ef2540ea21aa5607cd91.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/cbe75f66e6b4b4720b04ba9122657da1.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/cbe75f66e6b4b4720b04ba9122657da1.jpg"]},{"id":95,"goods_name":"松典（SONGDIAN） 数码学生相机便携CCD卡片机","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/10e599acd6fab90afaa29bc010738183.jpg","goods_stock":null,"goods_pirce":529,"delivery_fee":"10.00","create_time":1669049095,"tag":"rare","luckycoin":"888.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/61fc12fb455acb6ae03260922de61b5b.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/10e599acd6fab90afaa29bc010738183.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/10e599acd6fab90afaa29bc010738183.jpg"]},{"id":94,"goods_name":"松典（SONGDIAN） DC101L 数码相机","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/a4f2a63f20fb869a3b923547037aa9ca.jpg","goods_stock":null,"goods_pirce":596,"delivery_fee":"10.00","create_time":1669049039,"tag":"rare","luckycoin":"888.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/72d44aafd71f44a0ab96705890c694f5.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/a4f2a63f20fb869a3b923547037aa9ca.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/a4f2a63f20fb869a3b923547037aa9ca.jpg"]},{"id":87,"goods_name":"小霸王 Q99游艺机掌机迷你摇杆街机10.1英寸","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/24277eee55fbc93b06bfc7d6e1fa5fcc.jpg","goods_stock":null,"goods_pirce":568,"delivery_fee":"10.00","create_time":1669048218,"tag":"rare","luckycoin":"888.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/c4a170eeb9a27e79a07ada461fdf3b81.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/24277eee55fbc93b06bfc7d6e1fa5fcc.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/24277eee55fbc93b06bfc7d6e1fa5fcc.jpg"]},{"id":88,"goods_name":"暗影电竞 灵刀定制智能游戏鼠标","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/6b55bd238974daa5fe670f3559378221.jpg","goods_stock":null,"goods_pirce":180,"delivery_fee":"5.00","create_time":1669048312,"tag":"normal","luckycoin":"220.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/634b9d90cb3c2d200c471609ce91ca3b.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/6b55bd238974daa5fe670f3559378221.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/6b55bd238974daa5fe670f3559378221.jpg"]},{"id":94,"goods_name":"松典（SONGDIAN） DC101L 数码相机","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/a4f2a63f20fb869a3b923547037aa9ca.jpg","goods_stock":null,"goods_pirce":596,"delivery_fee":"10.00","create_time":1669049039,"tag":"rare","luckycoin":"888.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/72d44aafd71f44a0ab96705890c694f5.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/a4f2a63f20fb869a3b923547037aa9ca.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/a4f2a63f20fb869a3b923547037aa9ca.jpg"]},{"id":93,"goods_name":"天猫精灵 V10SE智慧屏智能AI","boxfl_id":5,"goods_images":"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/1f3a4765763f465c6030ff3d02fd44f5.jpg","goods_stock":null,"goods_pirce":599,"delivery_fee":"10.00","create_time":1669048796,"tag":"rare","luckycoin":"888.00","ms":"<p><img src=\"http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/db411731bf9387385d7d5250fe9b21ec.jpg\" data-filename=\"filename\" style=\"width: 470px;\"><br><\/p>","imagess":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/1f3a4765763f465c6030ff3d02fd44f5.jpg"],"images":["http:\/\/tongbayunmh.oss-accelerate.aliyuncs.com\/1f3a4765763f465c6030ff3d02fd44f5.jpg"]}],"ccknum":1}}';die;
        $order = Db::table('box_order')->where('out_trade_no', input('ooid'))->find();
        if (empty($order)) {
            $this->error('订单未找到哦');
        }
        if ($order['pay_time'] == null) {
            $this->error('订单还未支付哦');
        }
        if ($order['ischou'] == 1) {
            $this->error('订单已经抽过奖了');
        }
        // 查询该盲盒中的全部商品ID
        $goodsIds = Db::table('box_mhgoods')->where('boxfl_id', $order['boxfl_id'])->select();

        if (empty($goodsIds)) {
            throw new \Exception('奖品不足');
        }
        // 查询有效商品的概率信息标签:normal=高级,rare=稀有 ,supreme=史诗,legend=传说
        $box = Db::table('box_boxfl')->where('id', $order['boxfl_id'])->where('boxswitch', 1)->find();
        foreach ($goodsIds as &$goods) {
            if ($goods['tag'] == 'normal') {
                $goods['gl'] = $box['probability_gj'];
            } else if ($goods['tag'] == 'rare') {
                $goods['gl'] = $box['probability_xy'];
            } else if ($goods['tag'] == 'supreme') {
                $goods['gl'] = $box['probability_ss'];
            } else if ($goods['tag'] == 'legend') {
                $goods['gl'] = $box['probability_cs'];
            }
        }

        // 概率集合
        $prizeRate = array_column($goodsIds, 'gl');
        // 商品ID集合
        $goodsList = array_column($goodsIds, 'id');
        $goodsId = array();
        if ($order['num'] == 1) {
            $id =  self::rand($prizeRate, $goodsList);
            array_push($goodsId, $id);
        } else {
            for ($n = 1; $n <= $order['num']; $n++) {
                $id =  self::rand($prizeRate, $goodsList);
                array_push($goodsId, $id);
            }
        }
        //拿到抽中的奖品ID查询对应商品
        $shops = [];
        foreach ($goodsId as $gid) {
            $shop = Db::table('box_mhgoods')->where('id', $gid)->find();
            $tag_shop = Db::table('box_mhgoods')->where('boxfl_id', $shop['boxfl_id'])->where('tag', $shop['tag'])->select();
            foreach ($tag_shop as &$tag_goods_info) {
                if(empty($tag_goods_info['zj_rate'])|| $tag_goods_info['zj_rate']==0){
                    $tag_goods_info['gl'] = 0.01;
                }else{
                    $tag_goods_info['gl'] = $tag_goods_info['zj_rate'];
                }
            }
            // 概率集合
            $tag_prizeRate = array_column($tag_shop, 'gl');
            // 商品ID集合
            $tag_goodsList = array_column($tag_shop, 'id');
            $tag_goodsId = array();
            $tag_id =  self::rand($tag_prizeRate, $tag_goodsList);
            array_push($tag_goodsId, $tag_id);
            $zj_shop = Db::table('box_mhgoods')->where('id','in',$tag_goodsId)->find();
            
            array_push($shops, $zj_shop);
        }

        // print_r($shops);
        foreach ($shops as &$shop) {
            //处理抽到的奖品数据
            $image = explode(',', $shop['goods_images']);
            foreach ($image as $image_v) {
                $shop['imagess'][] = $image_v;
                $shop['images'][] = cdnurl($image_v, true);
            }
            if(empty($shop['delivery_fee'])){
                $shop['delivery_fee'] = 0;
            }elseif($shop['delivery_fee']<0){
                $shop['delivery_fee'] = 0;
            }
            // cdnurl($shop['goods_images'], true);
            //将抽到的商品写入仓库记录表
            $ooid = 'ALDMH' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            $s = Db::table('box_setting')->where('id', 1)->find();
            // $lucy = $shop['goods_pirce'] * ($s['dhbl'] / 100);
            $lucy = $shop['goods_pirce'] * ($s['dhbl'] / 100) * $s['zhbl'];
            $data = [
                'boxfl_id' => $order['boxfl_id'],
                'order_id' => $ooid,
                'user_id' => $this->auth->id,
                'out_trade_no' => $order['out_trade_no'],
                'goods_id' => $shop['id'],
                'goods_name' => $shop['goods_name'],
                'goods_image' => $shop['imagess'][0],
                'goods_coin_price' => $lucy,
                'goods_rmb_price' => $shop['goods_pirce'],
                'status' => 'bag',
                'delivery_fee' => $shop['delivery_fee'],
                'create_time' => time(),
            ];
            $box_prize_record_id = Db::table('box_prize_record')->insertGetId($data);
            $shop['box_prize_record_id'] = $box_prize_record_id;
            $mhlog = [
                'user_id' => $this->auth->id,
                'mhgoodsname' => $shop['goods_name'],
                'mhimage' => $shop['imagess'][0],
                'addtime' => time(),
                'mhgoods_id' => $shop['id'],
                'username' => $this->auth->nickname
            ];
            Db::table('box_mhlog')->insert($mhlog);
            $shop['isCheckeds'] = true;
        }
        if (!empty($jp)) {
            $order = Db::table('box_order')->where('out_trade_no', input('ooid'))->update(['ischou' => 1]);
        }
        $num = Db::table('box_card_list')->where('user_id', $this->auth->id)->where('status', 0)->count();
        $this->success('抽奖结果', ['data' => $shops, 'ccknum' => $num]);
    }
    /**
     * 试玩抽奖
     */
    public function SgetOne()
    {
        // $order = Db::table('box_order')->where('out_trade_no', $ooid)->find();
        // 查询该盲盒中的全部商品ID
        $goodsIds = Db::table('box_mhgoods')->where('boxfl_id', input('boxid'))->select();

        if (empty($goodsIds)) {
            throw new \Exception('奖品不足');
        }
        // 查询有效商品的概率信息标签:normal=高级,rare=稀有 ,supreme=史诗,legend=传说
        $box = Db::table('box_boxfl')->where('id', input('boxid'))->where('boxswitch', 1)->find();
        foreach ($goodsIds as &$goods) {
            if ($goods['tag'] == 'normal') {
                $goods['gl'] = $box['sw_probability_gj']?$box['sw_probability_gj']:$box['probability_gj'];
            } else if ($goods['tag'] == 'rare') {
                $goods['gl'] = $box['sw_probability_xy']?$box['sw_probability_xy']:$box['probability_xy'];
            } else if ($goods['tag'] == 'supreme') {
                $goods['gl'] = $box['sw_probability_ss']?$box['sw_probability_ss']:$box['probability_ss'];
            } else if ($goods['tag'] == 'legend') {
                $goods['gl'] = $box['sw_probability_cs']?$box['sw_probability_cs']:$box['probability_cs'];
            }
            
        }

        // 概率集合
        $prizeRate = array_column($goodsIds, 'gl');
        // 商品ID集合
        $goodsList = array_column($goodsIds, 'id');
        $goodsId = array();
        // print_r($goodsList);
        // exit;
        if (input('num') == 1) {
            $id =  self::rand($prizeRate, $goodsList);
            array_push($goodsId, $id);
        } else {
            for ($n = 1; $n <= input('num'); $n++) {
                $id =  self::rand($prizeRate, $goodsList);
                array_push($goodsId, $id);
            }
        }
        //拿到抽中的奖品ID查询对应商品
        // $gos = Db::table('box_mhgoods')->where('id', 'in', $goodsId)->select();
        // print_r($goodsId);
        $shops = [];
        foreach ($goodsId as $gid) {
            $shop = Db::table('box_mhgoods')->where('id', $gid)->find();
            $tag_shop = Db::table('box_mhgoods')->where('boxfl_id', $shop['boxfl_id'])->where('tag', $shop['tag'])->select();
            foreach ($tag_shop as &$tag_goods_info) {
                // if(empty($tag_goods_info['zj_rate'])|| $tag_goods_info['zj_rate']==0){
                //     $tag_goods_info['gl'] = 0.01;
                // }else{
                //     $tag_goods_info['gl'] = $tag_goods_info['zj_rate'];
                // }
                $tag_goods_info['gl'] = 1;
            }
            // 概率集合
            $tag_prizeRate = array_column($tag_shop, 'gl');
            // 商品ID集合
            $tag_goodsList = array_column($tag_shop, 'id');
            $tag_goodsId = array();
            $tag_id =  self::rand($tag_prizeRate, $tag_goodsList);
            array_push($tag_goodsId, $tag_id);
            $zj_shop = Db::table('box_mhgoods')->where('id','in',$tag_goodsId)->find();
            
            array_push($shops, $zj_shop);
        }

        // print_r($shops);
        foreach ($shops as &$shop) {
            //处理抽到的奖品数据
            $image = explode(',', $shop['goods_images']);
            foreach ($image as $image_v) {
                $shop['imagess'][] = $image_v;
                $shop['images'] = cdnurl($image_v, true);
            }
        }
        // $shop['']
        $this->success('试玩抽奖结果', $shops);
    }
    /*
    仓库开箱
    */
    public function getOnea()
    {
        $order = Db::table('box_yhbox')->where('id', input('id'))->find();
        $yhbox = Db::table('box_yhbox')->where('id', input('id'))->update(['status' => 2]);
        if (empty($yhbox)) {
            $this->error('已经开启过了哦');
        }
        // 查询该盲盒中的全部商品ID
        $goodsIds = Db::table('box_mhgoods')->where('boxfl_id', $order['boxfl_id'])->select();
        // print_r($goodsIds);
        if (empty($goodsIds)) {
            throw new \Exception('奖品不足');
        }
        // 查询有效商品的概率信息标签:normal=高级,rare=稀有 ,supreme=史诗,legend=传说
        $box = Db::table('box_boxfl')->where('id', $order['boxfl_id'])->where('boxswitch', 1)->find();
        foreach ($goodsIds as &$goods) {
            if ($goods['tag'] == 'normal') {
                $goods['gl'] = $box['probability_gj'];
            } else if ($goods['tag'] == 'rare') {
                $goods['gl'] = $box['probability_xy'];
            } else if ($goods['tag'] == 'supreme') {
                $goods['gl'] = $box['probability_ss'];
            } else if ($goods['tag'] == 'legend') {
                $goods['gl'] = $box['probability_cs'];
            }
        }

        // 概率集合
        $prizeRate = array_column($goodsIds, 'gl');
        // 商品ID集合
        $goodsList = array_column($goodsIds, 'id');
        $goodsId = array();
        $id =  self::rand($prizeRate, $goodsList);
        array_push($goodsId, $id);
        $shops = [];
        foreach ($goodsId as $gid) {
            $shop = Db::table('box_mhgoods')->where('id', $gid)->find();
            $tag_shop = Db::table('box_mhgoods')->where('boxfl_id', $shop['boxfl_id'])->where('tag', $shop['tag'])->select();
            foreach ($tag_shop as &$tag_goods_info) {
                if(empty($tag_goods_info['zj_rate'])|| $tag_goods_info['zj_rate']==0){
                    $tag_goods_info['gl'] = 0.01;
                }else{
                    $tag_goods_info['gl'] = $tag_goods_info['zj_rate'];
                }
            }
            // 概率集合
            $tag_prizeRate = array_column($tag_shop, 'gl');
            // 商品ID集合
            $tag_goodsList = array_column($tag_shop, 'id');
            $tag_goodsId = array();
            $tag_id =  self::rand($tag_prizeRate, $tag_goodsList);
            array_push($tag_goodsId, $tag_id);
            $zj_shop = Db::table('box_mhgoods')->where('id','in',$tag_goodsId)->find();
            
            array_push($shops, $zj_shop);
        }
        foreach ($shops as &$shop) {
            //处理抽到的奖品数据
            $image = explode(',', $shop['goods_images']);
            foreach ($image as $image_v) {
                $shop['imagess'][] = $image_v;
                $shop['images'][] = cdnurl($image_v, true);
            }
            //将抽到的商品写入仓库记录表
            $ooid = 'ALDMH' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            $s = Db::table('box_setting')->where('id', 1)->find();
            // $lucy = $shop['goods_pirce'] * ($s['dhbl'] / 100);
            $lucy = $shop['goods_pirce'] * ($s['dhbl'] / 100) * $s['zhbl'];
            $data = [
                'boxfl_id' => $order['boxfl_id'],
                'order_id' => $ooid,
                'user_id' => $this->auth->id,
                'goods_id' => $shop['id'],
                'goods_name' => $shop['goods_name'],
                'goods_image' => $shop['imagess'][0],
                'goods_coin_price' => $lucy,
                'goods_rmb_price' => $shop['goods_pirce'],
                'status' => 'bag',
                'delivery_fee' => $shop['delivery_fee'],
                'create_time' => time(),
            ];
            Db::table('box_prize_record')->insert($data);
            $mhlog = [
                'user_id' => $this->auth->id,
                'mhgoodsname' => $shop['goods_name'],
                'mhimage' => $shop['imagess'][0],
                'addtime' => time(),
                'mhgoods_id' => $shop['id'],
                'username' => $this->auth->nickname
            ];
            Db::table('box_mhlog')->insert($mhlog);
        }
        $num = Db::table('box_card_list')->where('user_id', $this->auth->id)->where('status', 0)->count();
        $this->success('抽奖结果', ['data' => $shops, 'ccknum' => $num]);
    }
    /**
     * 随机
     * @param array $rate 中奖概率集合:
     * <pre>
     * $rate = [
     *     0 => 10, // 第二个奖品概率10%
     *     1 => 5.88, // 第二个奖品概率5.88%
     *     1 => 35.60, // 第二个奖品概率35.6%
     * ];
     * </pre>
     * @param array $goods 奖品集合，顺序与rate字段一致:
     * $rate = [
     *     0 => '第一个奖品',
     *     1 => '第二个奖品',
     *     2 => '第三奖品',
     * ];
     * @return mixed
     * @author fuyelk <fuyelk@fuyelk.com>
     * @date 2021/07/10 21:08
     */
    private static function rand($rate = [], $goods = [])
    {
        // 将数据按概率降序排序
        array_multisort($rate, SORT_DESC, $goods);
        foreach ($rate as &$item) {
            $item = round($item, 2) * 100; // 扩大100倍避免小数
        }

        //奖项的设置和概率可以手动设置化;
        $total = array_sum($rate);
        $notice = [];
        foreach ($rate as $key => $value) {
            $randNumber = mt_rand(1, $total);
            if ($randNumber <= $value) {
                $notice = $goods[$key];
                break;
            } else {
                $total -= $value;
            }
        }
        return $notice;
    }
    /**
     * 图片上传
     */
    public function upload()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/uploads/ 目录下
        if ($file == null) {
            exit(json_encode(array('code' => 1, 'msg' => '没有文件上传')));
        }
        $info = $file->validate(['size' => 10000000, 'ext' => 'jpg,png,gif'])->move('../public/uploads');
        if ($info) {
            // 成功上传后 获取上传信息
            $info = str_replace("\\", "/", $info->getSaveName());
            $img = '/uploads/' . $info;
            // exit(json_encode(array()));
            $this->success('上传成功', ['data' => $img, 'url' => Request::instance()->domain()  . $img]);
        } else {
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }
    /**
     * 获取重抽卡数量
     */
    public function getCards()
    {
        $card = Db::table('box_card_list')->where('user_id', $this->auth->id)->where('status', 0)->select();
        $this->success('重抽卡', $card);
    }
    /**
     * 重抽卡使用
     */
    public function getCard()
    {
        //更改重抽卡状态 
        $card = Db::table('box_card_list')->where('id', input('cardid'))->find();
        if ($card['status'] != 0) {
            $this->error('重抽卡已使用');
        }
        $ret = Db::table('box_card_list')->where('id', input('cardid'))->update([
            'status' => 1,
            'sytime' => time(),
        ]);
        // print_r($ret);
        // exit;
        $shop = Db::table('box_prize_record')->where('id', input('id'))->find();
        // echo 111;
        // exit;
        $data = [
            'boxfl_id' => $shop['boxfl_id'],
            'user_id' => $this->auth->id,
            'status' => 1,
            'addtime' => time()
        ];
        Db::table('box_yhbox')->insert($data);
        //删除盒柜内商品
        $ret = Db::table('box_prize_record')->where('id', input('id'))->delete();
        if (empty($ret)) {
            $this->error('使用失败');
        }
        $this->success('使用成功,请去盒柜重新开盒');
        // $order = Db::table('box_order')->where('out_trade_no', input('ooid'))->find();
        // $this->getOne(input('ooid'));
    }
    /**
     * 获取优惠券
     */
    public function getWcoupon()
    {
        $cou = Db::table('box_coupon_list')->where('user_id', $this->auth->id)->where('status', 0)->select();

        if (empty($cou)) {
            $this->error('暂无优惠券哦');
        } else {
            $id = [];
            foreach ($cou as &$c) {
                $id[] = $c['coupon_id'];
            }
            $coulist = Db::table('box_coupon')->where('id', 'in', $id)->where('end_time', '>', time())->select();
            foreach ($coulist as &$coupon) {
                $coupon['endtime_v'] = date('Y-m-d H:i:s', $coupon['end_time']);
            }

            $this->success('查询成功', ['list' => $coulist]);
        }
    }
    /**
     * 领取优惠券
     */
    public function receiveCoupon()
    {
        // 接收优惠券ID 可能是单个可能是多个
        $post = input('post.');
        //将优惠券ID单独拿出来
        $cid = $post['cid'];
        if (is_array($cid)) {
            $coupon = Db::table('box_coupon')->where('id', 'in', $cid)->select();
            //先循环查询到的优惠卷
            foreach ($coupon as $cou) {
                //然后在循环添加数据
                foreach ($cid as $id) {
                    // 根据优惠券ID查询对应的优惠券
                    $data = [
                        'user_id' => $this->auth->id,
                        'coupon_id' => $id,
                        'status' => 0
                    ];
                    if ($id == $cou['id']) {
                        $data['couname'] = $cou['couponname'];
                        $ret = Db::table('box_coupon_list')->insert($data);
                    }
                }
            }
        } else {
            $coupon = Db::table('box_coupon')->where('id', $cid)->find();
            $data = [
                'user_id' => $this->auth->id,
                'coupon_id' => $cid,
                'couname' => $coupon['couponname'],
                'status' => 0
            ];
            $ret = Db::table('box_coupon_list')->insert($data);
        }
        if (empty($ret)) {
            $this->error('领取失败请重试');
        } else {
            $this->success('领取成功', []);
        }
    }
    /*订单数量接口*/
    public function getNum()
    {
        $dfknum = Db::table('box_shoporder')->where('status', 'unpay')->where('user_id', $this->auth->id)->where('pay_method', '<>', 'sqfh')->count();
        $dfhnum = Db::table('box_shoporder')->where('status', 'used')->where('user_id', $this->auth->id)->count();
        $yfhnum = Db::table('box_shoporder')->where('status', 'refund')->where('user_id', $this->auth->id)->count();
        // $dfhnum = Db::table('box_shoporder')->where('status','used')->count();
        $arr = [
            'dfk' => $dfknum,
            'dfh' => $dfhnum,
            'yfh' => $yfhnum,
        ];
        $this->success('订单数量', $arr);
    }
    public function getSite()
    {
        $site = Db::table('box_config')->where('name', 'name')->find();
        $this->success('盲盒名称', $site['value']);
    }
    public function getApp()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $this->success('APP下载地址', $s['appurl']);
    }
    public function getDomain()
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $this->success('域名', $s['domain']);
    }


    /**
     * 盲盒首页分类
     */
    public function getBanner()
    {
        $banner = Db::table('box_banner')->where('bswitch', 1)->where('tag','mhsy')->order('weigh DESC')->select();
        if (empty($banner)) {
            $this->error('未找到轮播图');
        }
        foreach ($banner as &$banner_v) {
            $banner_v['image'] = cdnurl($banner_v['image'], true);
        }
        $this->success('轮播图数据', $banner);
    }

}
