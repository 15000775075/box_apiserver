<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use PDO;
use wxpay\wxpay;
use alipay\Alipay;
use epay\epay;
class Order extends Api
{
    protected $noNeedRight = '*';
    protected $noNeedLogin = ['notifyx'];
    /**
     * 获取商城订单
     */
    public function getOrders()
    {
        $where  = [];
        //状态为1是待付款
        if (input('zt') == 1) {

            $order = Db::table('box_shoporder')->where('user_id', $this->auth->id)->where('status','unpay')->where('pay_method','<>','sqfh')->order('create_time DESC')->select();
        } else if (input('zt') == 2) {

            $order = Db::table('box_shoporder')->where('user_id', $this->auth->id)->where('status','used')->order('create_time DESC')->select();
        } else if (input('zt') == 3) {

            $order = Db::table('box_shoporder')->where('user_id', $this->auth->id)->where('status','refund')->order('create_time DESC')->select();
        } else if (input('zt') == 4) {

            $order = Db::table('box_shoporder')->where('user_id', $this->auth->id)->where('status','ywc')->order('create_time DESC')->select();
        }
        // $order = Db::table('box_shoporder')->where('user_id', $this->auth->id)->where($where)->order('create_time DESC')->select();
        foreach ($order as &$order_v) {
            $order_v['image'] = cdnurl($order_v['image'], true);
            if ($order_v['status'] == 'unpay') {
                $order_v['status'] = '待支付';
            } else if ($order_v['status'] == 'used') {
                $order_v['status'] = '待发货';
            } else if ($order_v['status'] == 'refund') {
                $order_v['status'] = '已发货';
            } else if ($order_v['status'] == 'ywc') {
                $order_v['status'] = '已完成';
            } else if ($order_v['status'] == 'undei') {
                $order_v['status'] = '已关闭';
            }
            //快递公司:yuantong=圆通速递,yunda=韵达快递,shentong=申通快递,zhongtong=中通快递,jtexpress=极兔速递,shunfeng=顺丰速运,youzhengguonei=邮政快递,ems=EMS,jd=京东物流,debangkuaidi=德邦快递
            switch ($order_v['kdgs']) {
                case 'yuantong':
                    $order_v['kdgs_v'] = '圆通速递';
                    break;
                case 'yunda':
                    $order_v['kdgs_v'] = '韵达快递';
                    break;
                case 'shentong':
                    $order_v['kdgs_v'] = '申通快递';
                    break;
                case 'zhongtong':
                    $order_v['kdgs_v'] = '中通快递';
                    break;
                case 'jtexpress':
                    $order_v['kdgs_v'] = '极兔速递';
                    break;
                case 'shunfeng':
                    $order_v['kdgs_v'] = '顺丰速运';
                    break;
                case 'youzhengguonei':
                    $order_v['kdgs_v'] = '邮政快递';
                    break;
                case 'ems':
                    $order_v['kdgs_v'] = 'EMS';
                    break;
                case 'jd':
                    $order_v['kdgs_v'] = '京东物流';
                    break;
                case 'debangkuaidi':
                    $order_v['kdgs_v'] = '德邦快递';
                    break;
                default:
                    break;
            }
        }

        $this->success('订单数据', $order);
    }
    /**
     * 查看单个订单详情
     */
    public function getOrder()
    {
        // $order =  Db::table('box_shoporder')->where('out_trade_no', input('ooid'))->find();
        $order =  Db::table('box_shoporder')->where('id', input('ooid'))->find();
        $order['image'] = cdnurl($order['image'], true);
        if ($order['status'] == 'unpay') {
            $order['status'] = '待支付';
        } else if ($order['status'] == 'used') {
            $order['status'] = '待发货';
        } else if ($order['status'] == 'refund') {
            $order['status'] = '已发货';
        } else if ($order['status'] == 'ywc') {
            $order['status'] = '已完成';
        } else if ($order['status'] == 'undei') {
            $order['status'] = '已关闭';
        }
        if($order['pay_method']=='xiguazi'){
            $order['pay_rmb'] = $order['pay_money'];
        }else if($order['pay_method']=='lucyk'){
            $order['pay_rmb'] = $order['pay_coin'];
        }
        $this->success('订单数据', $order);
    }
    /**
     * 获取仓库数据
     */
    public function getMhOrder()
    {
        //根据用户ID查询开箱记录
        $where = [];
        if (input('status') == 1) {
            //代表是已回收
            $where = [
                'status' => 'exchange'
            ];
        } else {
            $where = [
                'status' => 'bag'
            ];
        }
        $list = Db::table('box_prize_record')->where('user_id', $this->auth->id)->where($where)->order('create_time DESC')->select();
        if (empty($list)) {
            $this->success('未找到数据', $list);
        }
        $id = [];
        foreach ($list as &$list_v) {
            $list_v['goods_image'] = cdnurl($list_v['goods_image'], true);
            $list_v['hstime'] = date('Y-m-d H:i:s', $list_v['hstime']);
            $id[] = $list_v['goods_id'];
            if ($list_v['status'] == 'bag') {
                $list_v['status_v'] = '盒柜';
            } else if ($list_v['status'] == 'exchange') {
                $list_v['status_v'] = '已回收';
            } else if ($list_v['status'] == 'delivery') {
                $list_v['status_v'] = '申请发货';
            } else if ($list_v['status'] == 'received') {
                $list_v['status_v'] = '已收货';
            }
        }

        $shops = Db::table('box_mhgoods')->where('id', 'in', $id)->select();
        foreach ($list as &$l) {
            foreach ($shops as $shop) {
                if ($l['goods_id'] == $shop['id']) {
                    $l['luyck'] = $shop['luckycoin'];
                }
            }
        }
        $cardjl = Db::table('box_card_list')->where('user_id', $this->auth->id)->find();
        if (empty($cardjl)) {
            $this->success('查询成功', ['list' => $list, 'cck' => '0']);
        } else {
            $this->success('查询成功', ['list' => $list, 'cck' => '1']);
        }
    }
    /**
     * 批量申请发货接口
     */
    public function sqfh_pl()
    {
        $prize_record = Db::table('box_prize_record')->where('id','in',input('ids'))->where('user_id',$this->auth->id)->select();
        if(count($prize_record)<3){
            $yunfei = Db::table('box_prize_record')->where('id','in',input('ids'))->sum('delivery_fee');
        }else{
            $yunfei = 0;
        }
        $addres = Db::table('box_user_address')->where('id', input('addresid'))->find();
        $ooid = 'ALDMH' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $post = input();
        Db::table('box_prize_record')->where('id','in',input('ids'))->where('user_id',$this->auth->id)->update(['order_id'=>$ooid]);
        // var_dump($prize_record);die;
        foreach ($prize_record as $k => $s){
            //当运费为0时
            if ($yunfei<=0) {
                $data = [
                    'shop_id' => $s['goods_id'],
                    'shop_name' => $s['goods_name'],
                    'image' => $s['goods_image'],
                    'num' => 1,
                    'user_id' => $this->auth->id,
                    'pay_method' => 'sqfh',
                    'out_trade_no' => $ooid,
                    'status' => 'used',
                    'address' => $addres['province'] . $addres['city'] . $addres['area'] . $addres['detail'],
                    'username' => $addres['name'],
                    'mobile' => $addres['mobile'],
                    'delivery_fee' => 0,
                    'create_time'=>time(),
                ];
                $ret = Db::table('box_shoporder')->insert($data);
                if (empty($ret)) {
                    // $this->error('申请失败');
                }
                Db::table('box_prize_record')->where('id', $s['id'])->update(['status' => 'delivery']);
                // $this->success('发货成功');
            }else{ //运费不为0
                $data = [
                    'shop_id' => $s['goods_id'],
                    'shop_name' => $s['goods_name'],
                    'image' => $s['goods_image'],
                    'num' => 1,
                    'user_id' => $this->auth->id,
                    'pay_method' => 'sqfh',
                    'out_trade_no' => $ooid,
                    'status' => 'unpay',
                    'address' => $addres['province'] . $addres['city'] . $addres['area'] . $addres['detail'],
                    'username' => $addres['name'],
                    'mobile' => $addres['mobile'],
                    'delivery_fee' => $s['delivery_fee'],
                    'create_time'=>time(),
                ];
                $ret = Db::table('box_shoporder')->insert($data);
            }
        }
        if ($yunfei<=0) {
            $this->success('发货成功');
        }
        if ($post['terminal'] == 0) {
            //如果为0就是H5
            $epay = new epay;
            if ($post['payfs'] == 'alipay') {
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epaysqfhnotifyx';
                $epay->goePay($ooid, 'alipay', '支付运费', $yunfei,$notifyurl,$this->request->domain() . '/h5/#/pages/mall/paySuccexx');
            }else if($post['payfs'] == 'wechat'){
                $notifyurl = $this->request->domain() . '/index.php/api/pay/epaysqfhnotifyx';
                $epay->goePay($ooid, 'wechat', '支付运费', $yunfei,$notifyurl,$this->request->domain() . '/h5/#/pages/mall/paySuccexx');
            }
        }
        if ($post['terminal'] == 1) {
            // 如果为1就是小程序
            $this->payJoinfee('支付运费', $ooid, $yunfei);
        }
        if ($post['terminal'] == 2) {
            //如果为2就是APP
            $alipay = new Alipay();
            $notice = $this->request->domain() . '/index.php/api/pay/sqfhnotifyx';
            $alipay->pay('支付运费',$ooid,$yunfei,$notice);
        }
    }
    /**
     * 申请发货接口
     */
    public function sqfh()
    {
        $s = Db::table('box_prize_record')->where('id', input('id'))->find();
        $addres = Db::table('box_user_address')->where('id', input('addresid'))->find();
        $ooid = 'ALDMH' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $post = input();
        //当运费为0时
        if ($s['delivery_fee'] == 0) {
            $data = [
                'shop_id' => $s['goods_id'],
                'shop_name' => $s['goods_name'],
                'image' => $s['goods_image'],
                'num' => 1,
                'user_id' => $this->auth->id,
                'pay_method' => 'sqfh',
                'out_trade_no' => $ooid,
                'status' => 'used',
                'address' => $addres['province'] . $addres['city'] . $addres['area'] . $addres['detail'],
                'username' => $addres['name'],
                'mobile' => $addres['mobile'],
                'delivery_fee' => 0,
                'create_time'=>time(),
            ];
            $ret = Db::table('box_shoporder')->insert($data);
            if (empty($ret)) {
                $this->error('申请失败');
            }
            Db::table('box_prize_record')->where('id', input('id'))->update(['status' => 'delivery']);
            $this->success('发货成功');
        } else {
            if ($post['terminal'] == 0) {
                    $data = [
                    'shop_id' => $s['goods_id'],
                    'shop_name' => $s['goods_name'],
                    'image' => $s['goods_image'],
                    'num' => 1,
                    'user_id' => $this->auth->id,
                    'pay_method' => 'sqfh',
                    'out_trade_no' => $ooid,
                    'status' => 'unpay',
                    'address' => $addres['province'] . $addres['city'] . $addres['area'] . $addres['detail'],
                    'username' => $addres['name'],
                    'mobile' => $addres['mobile'],
                    'delivery_fee' => $s['delivery_fee'],
                    'create_time'=>time(),
                ];
                $ret = Db::table('box_shoporder')->insert($data);
                //如果为0就是H5
                    $epay = new epay;
                    if ($post['payfs'] == 'alipay') {
                        $notifyurl = $this->request->domain() . '/index.php/api/pay/epaysqfhnotifyx';
                        $epay->goePay($ooid, 'alipay', '支付运费', $s['delivery_fee'],$notifyurl,$this->request->domain() . '/h5/#/pages/mall/paySuccexx');
                    }else if($post['payfs'] == 'wechat'){
                        $notifyurl = $this->request->domain() . '/index.php/api/pay/epaysqfhnotifyx';
                        $epay->goePay($ooid, 'wechat', '支付运费', $s['delivery_fee'],$notifyurl,$this->request->domain() . '/h5/#/pages/mall/paySuccexx');
                    }
            } else if ($post['terminal'] == 1) {
                $data = [
                    'shop_id' => $s['goods_id'],
                    'shop_name' => $s['goods_name'],
                    'image' => $s['goods_image'],
                    'num' => 1,
                    'user_id' => $this->auth->id,
                    'pay_method' => 'sqfh',
                    'out_trade_no' => $ooid,
                    'status' => 'unpay',
                    'address' => $addres['province'] . $addres['city'] . $addres['area'] . $addres['detail'],
                    'username' => $addres['name'],
                    'mobile' => $addres['mobile'],
                    'delivery_fee' => $s['delivery_fee'],
                    'create_time'=>time(),
                ];
                $ret = Db::table('box_shoporder')->insert($data);
                // 如果为1就是小程序
                $this->payJoinfee('支付运费', $ooid, $s['delivery_fee']);
            } else if ($post['terminal'] == 2) {
                $data = [
                    'shop_id' => $s['goods_id'],
                    'shop_name' => $s['goods_name'],
                    'image' => $s['goods_image'],
                    'num' => 1,
                    'user_id' => $this->auth->id,
                    'pay_method' => 'alipay',
                    'out_trade_no' => $ooid,
                    'status' => 'unpay',
                    'address' => $addres['province'] . $addres['city'] . $addres['area'] . $addres['detail'],
                    'username' => $addres['name'],
                    'mobile' => $addres['mobile'],
                    'delivery_fee' => $s['delivery_fee'],
                    'create_time'=>time(),
                ];
                $ret = Db::table('box_shoporder')->insert($data);
                //如果为2就是APP
            $alipay = new Alipay();
            $notice = $this->request->domain() . '/index.php/api/pay/sqfhnotifyx';
            $alipay->pay('支付运费',$ooid,$s['delivery_fee'],$notice);
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
        $notifyurl = $this->request->domain() . '/index.php/api/order/notifyx';
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
            $this->handle($out_trade_no, $transaction_id);
        }
        $xml = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        echo $xml;
    }
    //订单处理
    public function handle($ooid, $tranid)
    {
        $ret = Db::table('box_shoporder')->where('out_trade_no', $ooid)->update(['status' => 'used', 'pay_time' => time(), 'transaction_id' => $tranid]);
        Db::table('box_prize_record')->where('order_id', $ooid)->update(['status' => 'delivery']);
    }
    
    //查询复选回收数据
    public function fuxuan_huishou_data(){
        //根据用户ID查询开箱记录
        $ids = $this->request->post('ids/a');
        $ids = implode(',',$ids);
        $list = Db::table('box_prize_record')->where('user_id', $this->auth->id)->whereIn('id',$ids)->order('create_time DESC')->select();
        if (empty($list)) {
            $this->success('未找到数据', $list);
        }
        $id = [];
        foreach ($list as &$list_v) {
        
            $list_v['goods_image'] = cdnurl($list_v['goods_image'], true);
            $list_v['hstime'] = date('Y-m-d H:i:s', $list_v['hstime']);
            $id[] = $list_v['goods_id'];
            if ($list_v['status'] == 'bag') {
                $list_v['status_v'] = '盒柜';
            } else if ($list_v['status'] == 'exchange') {
                $list_v['status_v'] = '已回收';
            } else if ($list_v['status'] == 'delivery') {
                $list_v['status_v'] = '申请发货';
            } else if ($list_v['status'] == 'received') {
                $list_v['status_v'] = '已收货';
            }
            // $list_v['goods_type'] = Db::name('mhgoods')->where('id',$list_v['goods_id'])->value('type');
        }

        $shops = Db::table('box_mhgoods')->where('id', 'in', $id)->select();
        $jindou_num = 0;
        foreach ($list as &$l) {
            // foreach ($shops as $shop) {
            //     if ($l['goods_id'] == $shop['id']) {
            //         $jindou_num += $shop['luckycoin'];
            //         $l['luyck'] = $shop['luckycoin'];
            //     }
            // }
            $l['luyck'] = $l['goods_coin_price'];
            $jindou_num += $l['goods_coin_price'];
        }
        $cardjl = Db::table('box_card_list')->where('user_id', $this->auth->id)->find();
        if (empty($cardjl)) {
            $this->success('查询成功', ['list' => $list,'count'=>count($list), 'cck' => '0','jindou_num'=>$jindou_num]);
        } else {
            $this->success('查询成功', ['list' => $list,'count'=>count($list),  'cck' => '1','jindou_num'=>$jindou_num]);
        }
    }
}
