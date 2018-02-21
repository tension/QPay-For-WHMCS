<?php
// NeWorld Manager 开始

// 引入文件
require  ROOTDIR . '/modules/addons/NeWorld/library/class/NeWorld.Common.Class.php';

// NeWorld Manager 结束

function QPay_MetaData() {
    return array(
        'DisplayName' => 'QQ钱包支付(NeWorld)',
        'APIVersion' => '1.1',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function QPay_config() {
	
    $configarray = [
    	"FriendlyName" => [
    		"Type" => "System",
    		"Value"=>"QQ钱包支付(NeWorld)"
    	],
		"MCHID" => [
			"FriendlyName" => "商户号",
			"Type" => "text",
			"Size" => "32",
		],
		"KEY" => [
			"FriendlyName" => "商户支付密钥",
			"Type" => "text",
			"Size" => "32",
		],
		"checkTime" => [
			"FriendlyName" => "检查时间",
			"Type" => "text",
			"Size" => "5",
			"Default" => "5",
			"Description" => "单位：秒 支付状态检查间隔",
		],
    ];
	
	
	return $configarray;
}

function QPay_link($params) {
    //微信支付初始化

	#系统变量
	$invoiceid 		= $params['invoiceid'];
	$description 	= $params["description"];
	$amount 		= $params['amount']; 			# Format: ##.##
    $amount 		= $amount * 100; 				# 微信支付使用分作单位
	$currency 		= $params['currency']; 			# Currency Code
	$companyname 	= $params['companyname'];
	$systemurl 		= $params['systemurl'];

    $notify_url 	= $systemurl . "/modules/gateways/QPay/notify_url.php";
		
	include_once("QPay/lib/QPayPubHelper.php");

	/**
	* 流程：
	* 1、调用统一下单，取得code_url，生成二维码
	* 2、用户扫描二维码，进行支付
	* 3、支付完成之后，微信服务器会通知支付成功
	* 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
	*/
 	//使用统一支付接口
	$unifiedOrder = new UnifiedOrder_pub();
	//设置统一支付接口参数
	$unifiedOrder->setParameter("body","$description");//商品描述
	//自定义订单号，此处仅作举例
	$timeStamp = time();
	$out_trade_no = QPayConf_pub::MCHID."$timeStamp";
	$unifiedOrder->setParameter("out_trade_no","$invoiceid");//商户订单号 
	$unifiedOrder->setParameter("total_fee","$amount");//总金额
	$unifiedOrder->setParameter("notify_url","$notify_url");//通知地址 
	$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
	$unifiedOrder->setParameter("attach","$invoiceid");//附加数据 
	$unifiedOrder->setParameter("product_id","$invoiceid");//商品ID
	//非必填参数，商户可根据实际情况选填
	//$unifiedOrder->setParameter("device_info","XXXX");//设备号 
	//$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
	//$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间 
	//获取统一支付接口结果
	$unifiedOrderResult = $unifiedOrder->getResult();
	//print_r($unifiedOrderResult);die();
	
	//商户根据实际情况设置相应的处理流程
	if ($unifiedOrderResult["return_code"] == "FAIL") {
		//商户自行增加处理流程
		echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
	} elseif($unifiedOrderResult["result_code"] == "FAIL") {
		//商户自行增加处理流程
		//echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
		//echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
	} elseif($unifiedOrderResult["code_url"] != NULL) {
		//从统一支付接口获取到code_url
		$code_url = $unifiedOrderResult["code_url"];
		//商户自行增加处理流程
	}
	$result['qrcode'] 		= $code_url;
	$result['invoiceid'] 	= $invoiceid;
	$result['returnurl'] 	= $params['returnurl'];
	$result['checkTime'] 	= checkTime * 1000;
	$ext = new NeWorld\Extended;
	$code = $ext->getSmarty([
	    'dir' 	=> __DIR__ . '/QPay/',
        'file' 	=> 'QPay',
        'vars' 	=> $result,
    ]);
    
	if (stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
		return $code;
	} else {
		return '<img style="width: 200px" src="'.$systemurl.'/modules/gateways/QPay/QPay.png" alt="QQ钱包支付" />';
	}
}

function QPay_refund($params) {
	include_once("QPay/lib/QPayPubHelper.php");
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];
    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];
    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];
    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

	//第三方应用授权令牌,商户授权系统商开发模式下使用
	$appAuthToken = "";//根据真实值填写
    
    $invoice = \Illuminate\Database\Capsule\Manager::table('tblaccounts')->where('transid', $transactionIdToRefund)->first();
	
	//创建退款请求builder,设置参数
	//商户订单号
	$out_trade_no = $invoice->invoiceid;
	//商户退款单号，商户自定义，此处仅作举例
	$out_refund_no = $out_trade_no . $timeStamp;
	//总金额需与订单号out_trade_no对应，demo中的所有订单的总金额为1分
	$total_fee = $invoice->amountin * 100;
	//退款金额
    $refund_fee = $refundAmount * 100;
	
	//使用退款接口
	$refund = new Refund_pub();
	//设置必填参数
	//appid已填,商户无需重复填写
	//mch_id已填,商户无需重复填写
	//noncestr已填,商户无需重复填写
	//sign已填,商户无需重复填写
	$refund->setParameter("out_trade_no","$out_trade_no");//商户订单号
	$refund->setParameter("out_refund_no","$out_refund_no");//商户退款单号
	$refund->setParameter("total_fee","$total_fee");//总金额
	$refund->setParameter("refund_fee","$refund_fee");//退款金额
	$refund->setParameter("op_user_id",QPayConf_pub::MCHID);//操作员
	
	//调用结果
	$refundResult = $refund->getResult();
	//根据交易状态进行处理
	switch ($refundResult["return_code"]){
		case "SUCCESS":
			//echo "支付宝退款成功:"."<br>--------------------------<br>";
			//print_r($refundResult->getResponse());
		    $code =  [
		        // 'success' if successful, otherwise 'declined', 'error' for failure
		        'status' => 'success',
		        // Data to be recorded in the gateway log - can be a string or array
		        'rawdata' => date('Y-m-d h:i:sa', $timeStamp),
		        // Unique Transaction ID for the refund transaction
		        'transid' => $refundResult['out_refund_no'],
		        // Optional fee amount for the fee value refunded
		        'fees' => $refundResult['refund_fee']/100,
		    ];
		    logTransaction('QPay', json_encode($refundResult), $code['status']);
			break;
		case "FAIL":
			//echo "支付宝退款失败!!!"."<br>--------------------------<br>";
			//if(!empty($refundResult->getResponse())){
			//	print_r($refundResult->getResponse());
			//}
			$code = array(
		        // 'success' if successful, otherwise 'declined', 'error' for failure
		        'status' => 'error',
		        // Data to be recorded in the gateway log - can be a string or array
		        'rawdata' => date("Y-m-d h:i:sa", $time_stamp),
		    );
		    logTransaction('QPay', json_encode($refundResult), $code['status']);
			break;
		default:
			echo "不支持的交易状态，交易返回异常!!!";
			break;
	}
	return $code;
}

if ( !function_exists('isMobile') ) {
	function isMobile() {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$mobile_browser = Array(
			"mqqbrowser", //手机QQ浏览器
			"opera mobi", //手机opera
			"juc","iuc",//uc浏览器
			"fennec","ios","applewebKit/420","applewebkit/525","applewebkit/532","ipad","iphone","ipaq","ipod",
			"iemobile", "windows ce",//windows phone
			"240×320","480×640","acer","android","anywhereyougo.com","asus","audio","blackberry","blazer","coolpad" ,"dopod", "etouch", "hitachi","htc","huawei", "jbrowser", "lenovo","lg","lg-","lge-","lge", "mobi","moto","nokia","phone","samsung","sony","symbian","tablet","tianyu","wap","xda","xde","zte"
		);
		$is_mobile = false;
			foreach ($mobile_browser as $device) {
			if (stristr($user_agent, $device)) {
				$is_mobile = true;
				break;
			}
		}
		return $is_mobile;
	}
}