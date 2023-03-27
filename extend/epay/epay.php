<?php

namespace epay;

use epay\lib\EpayCore;
use think\Db;
class epay
{
	public function goePay($out_trade_no, $type, $name, $money, $notify_url, $return_url)
	{
		/**************************请求参数**************************/
		// $notify_url = "http://127.0.0.1/SDK/notify_url.php";
		//需http://格式的完整路径，不能加?id=123这类自定义参数

		//页面跳转同步通知页面路径
		// $return_url = "http://127.0.0.1/SDK/return_url.php";
		//需http://格式的完整路径，不能加?id=123这类自定义参数

		//商户订单号
		// $out_trade_no = $_POST['WIDout_trade_no'];
		//商户网站订单系统中唯一订单号，必填

		//支付方式（可传入alipay,wxpay,qqpay,bank,jdpay）
		// $type = $_POST['type'];
		//商品名称
		// $name = $_POST['WIDsubject'];
		//付款金额
		// $money = $_POST['WIDtotal_fee'];


		/************************************************************/
        $s = Db::table('box_setting')->where('id',1)->find();
        $epay_config = [
            'pid' => $s['yzfid'],
            'key' => $s['yzfkey'],
            'apiurl' => $s['yzfurl']
        ];
		//构造要请求的参数数组，无需改动
		$parameter = array(
			"pid" => $epay_config['pid'],
			"type" => $type,
			"notify_url" => $notify_url,
			"return_url" => $return_url,
			"out_trade_no" => $out_trade_no,
			"name" => $name,
			"money"	=> $money,
		);

		//建立请求
		$epay = new EpayCore($epay_config);
		$html_text = $epay->pagePay($parameter);
		echo $html_text;
	}
}
