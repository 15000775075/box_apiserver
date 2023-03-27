<?php

namespace alipay;

use aop\AopClient;
use aop\request\AlipayTradeAppPayRequest;
use think\Db;
// require_once './extend/aop/AopClient.php';

// require_once './extend/aop/request/AlipayTradeAppPayRequest.php';

class Alipay
{
    public function pay($name,$ooid,$pirce,$notice)
    {
        $s = Db::table('box_setting')->where('id', 1)->find();
        $aop = new AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $s['appId']; //AppID
        //应用私钥
        $aop->rsaPrivateKey = $s['rsaPrivateKey'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        //应用公钥
        $aop->alipayrsaPublicKey = $s['alipayrsaPublicKey'];
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeAppPayRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"支付\","
            . "\"subject\": \"$name\","
            . "\"out_trade_no\": \"$ooid\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"$pirce\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";
        $request->setNotifyUrl($notice);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        echo json_encode(['code'=>1,'data'=>$response,'ooid'=>$ooid]); //就是orderString 可以直接给客户端请求，无需再做处理。
    }
}
