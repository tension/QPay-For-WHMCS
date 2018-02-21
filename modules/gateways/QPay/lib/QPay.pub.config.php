<?php
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewayModule = "QPay"; # Enter your gateway module name here replacing template

$gatewayParams = getGatewayVariables($gatewayModule);

if (!$gatewayParams["type"]) {
	die("Module Not Activated");
}

define('QPay_MCHID', $gatewayParams['MCHID']);
define('QPay_KEY', $gatewayParams['KEY']);
define('checkTime', $gatewayParams['checkTime']);
define('CERT_ROOT', dirname(__FILE__));
/**
* 	配置账号信息
*/
class QPayConf_pub {
	//=======【基本信息设置】=====================================
	//受理商ID，身份标识
	const MCHID = QPay_MCHID;
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = QPay_KEY;
	//=======【证书路径设置】=====================================
	//证书路径,注意应该填写绝对路径
	const SSLCERT_PATH = CERT_ROOT . '/../cert/apiclient_cert.pem';
	const SSLKEY_PATH = CERT_ROOT . '/../cert/apiclient_key.pem';
	
	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;
}
	
?>