<?php

namespace app\api\controller;
use app\common\controller\Api;
use AopClient;
use AopCertification;
use AlipayTradeQueryRequest;
use AlipayTradeWapPayRequest;
use AlipayTradeAppPayRequest;
use think\Db;
use epay\lib\EpayCore;

class Pay extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 充值预订单
     */
    public function goPay()
    {
        $price = $this->request->post("price");

        $out_trade_no = 'PAY' . date('Ymd') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $data = [
            'user_id' => $this->auth->id,
            'coin_amount' => $price,
            'rmb_amount' => $price,
            'pay_method' => 'googlepay',
            'pay_rmb' => $price,
            'out_trade_no' => $out_trade_no,
            'transaction_id' => '',
            'alipay_trade_no' => '',
            'pay_time' => time(),
            'status' => 'unpay',
            'backend_read' => 0,
            'create_time' => time(),
            'update_time' => null,
            'delete_time' => null
        ];
        Db::table('box_recharge_order')->insertGetId($data);

        $this->success('预订单创建成功', ['out_trade_no' => $out_trade_no]);
    }

    /**
     * 充值回调
     */
    public function googlenotifyx()
    {

        //验证订单是否成功
        //$verify_result = $epay->verifyNotify();

        $out_trade_no=$this->request->post("out_trade_no");
        if (true) { //验证成功
            //商户订单号
            $out_trade_no = $this->request->post("out_trade_no");
            //交易状态
            $trade_status = $this->request->post("trade_status");
            //支付方式
            $type = $this->request->post("type");
            //支付金额
            $money = $this->request->post("money");

            if ($trade_status== 'SUCCESS')
            {
                //业务处理

                $order = Db::table('box_recharge_order')->where('out_trade_no', $out_trade_no)->where('status', 'unpay')->find();
                if (empty($order)) {

                    $this->error('订单不存在', null);
                    exit;
                }
                $ret = Db::table('box_recharge_order')->where('out_trade_no', $out_trade_no)->update(['pay_time' => time(), 'status' => 'paid']);
                $user = Db::table('box_user')->where('id', $order['user_id'])->find();
                if (!empty($ret)) {
                    $money=$user['money'] + $money;
                    $res = Db::table('box_user')->where('id', $user['id'])->update(['money' =>$money] );
//                    if (!empty($res)) {
//                        $user = Db::table('box_user')->where('id', $order['user_id'])->find();
//                    }
                }
                //验证成功返回
                $this->success('success', $money);
            } else {
                //验证失败
                $this->error('fail' );
            }
        }
    }
}

