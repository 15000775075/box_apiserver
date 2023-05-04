<?php

namespace app\api\controller;
// require_once '../request/AlipayTradeQueryRequest.php';1
// require_once '../request/AlipayTradeWapPayRequest.php';
// require_once '../request/AlipayTradeAppPayRequest.php';
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
    
    //测试回调
    public function test_notify(){
        $ooid = input('ooid','');
        $tranid = '测试'.time();
        $res = $this->handle($ooid,$tranid);
    }
    //订单处理
    function handle($ooid, $tranid)
    {
        // echo $ooid;die;
        $ret = Db::table('box_order')->where('out_trade_no', $ooid)->update(['status' => 'used', 'pay_time' => time(), 'transaction_id' => $tranid]);
        $order = Db::table('box_order')->where('out_trade_no', $ooid)->find();
        $s = Db::table('box_setting')->where('id', 1)->find();
        $user = Db::table('box_user')->where('id', $order['user_id'])->find();
        $order_count = Db::table('box_order')->where('user_id', $user['id'])->where('status','not like','unpay')->count();
        if (!empty($ret)) {
            if (!empty($user['pid'])) {
                //处理普通返佣
                $u = Db::table('box_user')->where('id', $user['pid'])->find();
                if ($u['isdl'] == 1) {
                    //如果是代理
                    //金币兑换比例 1:100
                    $bl = $s['zhbl'];
                    if(empty($u['sharebl']) || $u['sharebl']==null){
                        $fyje = $order['pay_coin'] * ($s['def_fenyong_bili'] / 100);
                    }else{
                        $fyje = $order['pay_coin'] * ($u['sharebl'] / 100);
                    }
                    //获取的返佣幸运币金额
                    $coin = $fyje;
                    $usercoin = Db::table('box_user')->where('id', $u['id'])->update(['money' => $u['money'] + $coin]);
                    if (!empty($usercoin)) {
                        //记录幸运币表
                        $coindata = [
                            'user_id' => $u['id'],
                            'before' => $u['money'],
                            'after' => $u['money'] + $coin,
                            'coin' => $coin,
                            'type' => 'fxfy',
                            'money_type'=>1,
                            'order_id' => $order['id'],
                            'create_time' => time()
                        ];
                        $coinjl = Db::table('box_coin_record')->insert($coindata);
                        //记录返佣明细表
                        $fyjldata = [
                            'user_id' => $u['id'],
                            'lytag' => 'kaihe',
                            'lxtag' => 'coin',
                            'coinnum' => $coin,
                            'laiyuan' => $user['nickname'].'开盒返佣',
                            'jltime' => time()
                        ];
                        $fyjl = Db::table('box_detailed')->insert($fyjldata);
                    }
                } else {
                    if($order_count<=1){ //邀请的用户只首次购买才有送
                        $box = Db::table('box_boxfl')->where('id', $s['boxfl_id'])->find();
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
                                'lxtag' => 'box',
                                'boxfl_id' => $s['boxfl_id'],
                                'laiyuan' => $user['nickname'].'开盒奖励' . $box['box_name'],
                                'jltime' => time()
                            ]);
                        }
                    }
                }
            }
        }
    }
    
    // wxmpPay
    //处理微信支付回调
    public function mhnotifyx()
    {
        $myfile = fopen("testfile.txt", "a");
        fwrite($myfile, "
        ");
        fwrite($myfile, json_encode($_POST));

        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
            //业务处理
            $order = Db::table('box_order')->where('out_trade_no', $_POST['out_trade_no'])->find();
            $ret = Db::table('box_order')->where('out_trade_no', $_POST['out_trade_no'])->update(['pay_time' => time(), 'status' => 'used', 'alipay_trade_no' => $_POST['trade_no']]);
            $user = Db::table('box_user')->where('id', $order['user_id'])->find();
            if (!empty($ret)) {
                $s = Db::table('box_setting')->where('id', 1)->find();
                if (!empty($user['pid'])) {
                    //处理返佣
                    $u = Db::table('box_user')->where('id', $user['pid'])->find();
                    if ($u['isdl'] == 1) {
                        //如果是代理
                        //金币兑换比例 1:100
                        $bl = $s['zhbl'];
                        $fyje = ceil($order['pay_coin'] * $u['sharebl']);
                        //获取的返佣幸运币金额
                        $coin = $fyje;
                        $usercoin = Db::table('box_user')->where('id', $u['id'])->update(['money' => $u['money'] + $coin]);
                        if (!empty($usercoin)) {
                            //记录幸运币表
                            $coindata = [
                                'user_id' => $u['id'],
                                'before' => $u['money'],
                                'after' => $u['money'] + $coin,
                                'coin' => $coin,
                                'type' => 'fxfy',
                                'money_type'=>1,
                                //'order_id' => $ooid,
                                'create_time' => time()
                            ];
                            $coinjl = Db::table('box_coin_record')->insert($coindata);
                            //记录返佣明细表
                            $fyjldata = [
                                'user_id' => $u['id'],
                                'lytag' => 'kaihe',
                                'lxtag' => 'coin',
                                'coinnum' => $coin,
                                'laiyuan' => $user['nickname'].'开盒返佣',
                                'jltime' => time()
                            ];
                            $fyjl = Db::table('box_detailed')->insert($fyjldata);
                        }
                    }else{
                        $box = Db::table('box_boxfl')->where('id', $s['boxfl_id'])->find();
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
                                'lxtag' => 'box',
                                'boxfl_id' => $s['boxfl_id'],
                                'laiyuan' => $user['nickname'].'开盒奖励' . $box['box_name'],
                                'jltime' => time()
                            ]);
                        }
                    }

                }
            }
            echo 'success';
        } else {
            echo 'fail';
        }
        fclose($myfile);
    }
    public function sqfhnotifyx()
    {
        $myfile = fopen("testfile.txt", "a");
        fwrite($myfile, "
        ");
        fwrite($myfile, json_encode($_POST));

        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
            $ret = Db::table('box_shoporder')->where('out_trade_no', $_POST['out_trade_no'])->update(['status' => 'used', 'pay_time' => time(), 'transaction_id' => $_POST['trade_no']]);
            Db::table('box_prize_record')->where('order_id', $_POST['out_trade_no'])->update(['status' => 'delivery']);
            echo 'success';
        } else {
            echo 'fail';
        }
        fclose($myfile);
    }
    public function shopnotifyx()
    {
        $myfile = fopen("testfile.txt", "a");
        fwrite($myfile, "
        ");
        fwrite($myfile, json_encode($_POST));

        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
            $ret = Db::table('box_shoporder')->where('out_trade_no', $_POST['out_trade_no'])->update(['status' => 'used', 'pay_time' => time(), 'alipay_trade_no' => $_POST['trade_no']]);
            $order = Db::table('box_shoporder')->where('out_trade_no', $_POST['out_trade_no'])->find();
            $user = Db::table('box_user')->where('id', $order['user_id'])->find();
            //更新用户幸运币余额
            $res = Db::table('box_user')->where('id', $order['user_id'])->update(['score' => ($user['score'] - $order['pay_coin'])]);
            Db::table('box_coin_record')->insert([
                'user_id' => $user['id'],
                'before' => $user['score'],
                'after' => $user['score'] - $order['pay_coin'],
                'coin' => $order['pay_coin'],
                'type' => 'pay_shop',
                'order_id' => $_POST['out_trade_no'],
                'create_time' => time()
            ]);
            echo 'success';
        } else {
            echo 'fail';
        }
        fclose($myfile);
    }
    public function epaymhnotifyx()
    {
        //计算得出通知验证结果
        $s = Db::table('box_setting')->where('id', 1)->find();
        $epay_config = [
            'pid' => $s['yzfid'],
            'key' => $s['yzfkey'],
            'apiurl' => $s['yzfurl']
        ];
        $epay = new EpayCore($epay_config);
        $verify_result = $epay->verifyNotify();

        if ($verify_result) { //验证成功

            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];

            //彩虹易支付交易号
            $trade_no = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];

            //支付方式
            $type = $_GET['type'];

            //支付金额
            $money = $_GET['money'];

            if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                //业务处理
                $order = Db::table('box_order')->where('out_trade_no', $out_trade_no)->find();
                if (empty($order)) {
                    echo '订单不存在';
                    exit;
                }
                $ret = Db::table('box_order')->where('out_trade_no', $out_trade_no)->update(['pay_time' => time(), 'status' => 'used']);
                $user = Db::table('box_user')->where('id', $order['user_id'])->find();
                if (!empty($ret)) {
                    $s = Db::table('box_setting')->where('id', 1)->find();
                    if (!empty($user['pid'])) {
                        //处理返佣
                        $u = Db::table('box_user')->where('id', $user['pid'])->find();
                        if ($u['isdl'] == 1) {
                            //如果是代理
                            //金币兑换比例 1:100
                            $bl = $s['zhbl'];
                            $fyje = $order['pay_coin'] * ($u['sharebl'] /100);
                            //获取的返佣幸运币金额
                            $coin = $fyje;
                            $usercoin = Db::table('box_user')->where('id', $u['id'])->update(['money' => $u['money'] + $coin]);
                            if (!empty($usercoin)) {
                                //记录幸运币表
                                $coindata = [
                                    'user_id' => $u['id'],
                                    'before' => $u['money'],
                                    'after' => $u['money'] + $coin,
                                    'coin' => $coin,
                                    'type' => 'fxfy',
                                    'money_type' => 1,
                                    //'order_id' => $ooid,
                                    'create_time' => time()
                                ];
                                $coinjl = Db::table('box_coin_record')->insert($coindata);
                                //记录返佣明细表
                                $fyjldata = [
                                    'user_id' => $u['id'],
                                    'lytag' => 'kaihe',
                                    'lxtag' => 'coin',
                                    'coinnum' => $coin,
                                    'laiyuan' => $user['nickname'].'开盒返佣',
                                    'jltime' => time()
                                ];
                                $fyjl = Db::table('box_detailed')->insert($fyjldata);
                            }
                        }else{
                                                    $box = Db::table('box_boxfl')->where('id', $s['boxfl_id'])->find();
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
                                'lxtag' => 'box',
                                'boxfl_id' => $s['boxfl_id'],
                                'laiyuan' => $user['nickname'].'开盒奖励' . $box['box_name'],
                                'jltime' => time()
                            ]);
                        }
                        }

                    }
                }
            }
            //验证成功返回
            echo "success";
        } else {
            //验证失败
            echo "fail";
        }
    }
    public function epayscnotifyx()
    {
        //计算得出通知验证结果
        $s = Db::table('box_setting')->where('id', 1)->find();
        $epay_config = [
            'pid' => $s['yzfid'],
            'key' => $s['yzfkey'],
            'apiurl' => $s['yzfurl']
        ];
        $epay = new EpayCore($epay_config);
        $verify_result = $epay->verifyNotify();

        if ($verify_result) { //验证成功

            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];

            //彩虹易支付交易号
            $trade_no = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];

            //支付方式
            $type = $_GET['type'];

            //支付金额
            $money = $_GET['money'];

            if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $ret = Db::table('box_shoporder')->where('out_trade_no', $out_trade_no)->update(['status' => 'used', 'pay_time' => time()]);
                $order = Db::table('box_shoporder')->where('out_trade_no', $out_trade_no)->find();
                $user = Db::table('box_user')->where('id', $order['user_id'])->find();
                //更新用户幸运币余额
                $res = Db::table('box_user')->where('id', $order['user_id'])->update(['score' => ($user['score'] - $order['pay_coin'])]);
                Db::table('box_coin_record')->insert([
                    'user_id' => $user['id'],
                    'before' => $user['score'],
                    'after' => $user['score'] - $order['pay_coin'],
                    'coin' => $order['pay_coin'],
                    'type' => 'pay_shop',
                    'order_id' => $out_trade_no,
                    'create_time' => time()
                ]);
            }
            //验证成功返回
            echo "success";
        } else {
            //验证失败
            echo "fail";
        }
    }
    public function epaysqfhnotifyx()
    {
        //计算得出通知验证结果
        $s = Db::table('box_setting')->where('id', 1)->find();
        $epay_config = [
            'pid' => $s['yzfid'],
            'key' => $s['yzfkey'],
            'apiurl' => $s['yzfurl']
        ];
        $epay = new EpayCore($epay_config);
        $verify_result = $epay->verifyNotify();

        if ($verify_result) { //验证成功

            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];

            //彩虹易支付交易号
            $trade_no = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];

            //支付方式
            $type = $_GET['type'];

            //支付金额
            $money = $_GET['money'];

            if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $ret = Db::table('box_shoporder')->where('out_trade_no', $out_trade_no)->update(['status' => 'used', 'pay_time' => time(), 'transaction_id' => $_POST['trade_no']]);
                Db::table('box_prize_record')->where('order_id', $out_trade_no)->update(['status' => 'delivery']);
            }
            //验证成功返回
            echo "success";
        } else {
            //验证失败
            echo "fail";
        }
    }



    /*
     * goole支付回调
     **/
    public function goolenotifyx()
    {
        //计算得出通知验证结果
        $s = Db::table('box_setting')->where('id', 1)->find();
        $epay_config = [
            'pid' => $s['yzfid'],
            'key' => $s['yzfkey'],
            'apiurl' => $s['yzfurl']
        ];
        $epay = new EpayCore($epay_config);
        $verify_result = $epay->verifyNotify();

        if ($verify_result) { //验证成功

            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];

            //彩虹易支付交易号
            $trade_no = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];

            //支付方式
            $type = $_GET['type'];

            //支付金额
            $money = $_GET['money'];

            if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $ret = Db::table('box_shoporder')->where('out_trade_no', $out_trade_no)->update(['status' => 'used', 'pay_time' => time()]);
                $order = Db::table('box_shoporder')->where('out_trade_no', $out_trade_no)->find();
                $user = Db::table('box_user')->where('id', $order['user_id'])->find();
                //更新用户幸运币余额
                $res = Db::table('box_user')->where('id', $order['user_id'])->update(['score' => ($user['score'] - $order['pay_coin'])]);
                Db::table('box_coin_record')->insert([
                    'user_id' => $user['id'],
                    'before' => $user['score'],
                    'after' => $user['score'] - $order['pay_coin'],
                    'coin' => $order['pay_coin'],
                    'type' => 'pay_shop',
                    'order_id' => $out_trade_no,
                    'create_time' => time()
                ]);
            }
            //验证成功返回
            echo "success";
        } else {
            //验证失败
            echo "fail";
        }
    }



    /**
     * 驗證google內購訂單
     *
     * @param $parsedJson 來自客戶端，一個訂單數據的JSON字符串
     * @return array
     */
    public function google()
    {
        $post = $_REQUEST;
        $parsedJson = $post['data'];
        if(empty($parsedJson))   $this->outPutJson(500,'數據域空');
        $parsedJson = json_decode($parsedJson, true);
        if(empty($parsedJson))   $this->outPutJson(500,'數據域空');
        $transactionId = $parsedJson['orderId'];
        $oid = $parsedJson['obfuscatedAccountId'];
        if(empty($transactionId))   $this->outPutJson(500,'google訂單號空');
        if(empty($oid))   $this->outPutJson(500,'訂單號空');
        if (!empty($transactionId)) {
            Order::I()->updateTransId($oid, $transactionId);
        }

        $pid = $parsedJson['productId'];
        $orderToken = $parsedJson['purchaseToken'];
        $packageName = $parsedJson['packageName'];
        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $url = 'https://www.googleapis.com/androidpublisher/v3/applications/'.$packageName.'/purchases/products/'.$pid.'/tokens/'.$orderToken.'?access_token='.$accessToken;
            $result = Http::get($url);
            $contents = json_decode($result,true);
            if (!empty($contents)) {
                if (isset($contents['error'])) {
                    $this->outPutJson(500,'請求的身份驗證憑據無效');
                }

                if($contents['consumptionState'] === 0 && $contents['purchaseState'] === 0){
                    //驗證成功  購買成功並且沒有消耗  google支付中客戶端如果沒有進行消耗是不能再次購買該商品
                    $res = Order::I()->finishPayIos($oid,$transactionId);
                    if($res === true){
                        $this->outPutJson(200,'正式購買成功');
                    }else {
                        $this->outPutJson(500,$res);
                    }
                }else{
                    //訂單驗證失敗
                    $this->outPutJson(500,'訂單狀態有誤');
                }
            }

        } else {
            $this->outPutJson(500,'TOKEN丟失');
        }
    }
    /**
     * google token
     *
     * @return array
     */
    private function getAccessToken()
    {


        $cacheKey = "google.kanshu.access_token";

        $accessToken = DI::Cache()->get($cacheKey);

        if ($accessToken) {
            return $accessToken;
        }

        $url = 'https://accounts.google.com/o/oauth2/token';
        $data['refresh_token'] = Config::get('google.refresh_token');
        $data['client_id'] = Config::get('google.clientid');
        $data['client_secret'] = Config::get('google.key');
        $data['grant_type'] = 'refresh_token';
        $response = Http::post($url,$data);
        $result = json_decode($response, true);

        if ($result) {
            if (isset($result['access_token'])) {
                DI::Cache()->set($cacheKey, $result['access_token'], $result['expires_in']);
                return $result['access_token'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
