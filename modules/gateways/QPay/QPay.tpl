<!--微信支付 AJAX 跳转-->
<script>
//设置每隔1000毫秒执行一次 load() 方法
setInterval(function(){ load() }, {$checkTime});
function load() {
	$.ajax({
		cache: false,
		type: "GET",
		url: "{$systemurl}/modules/gateways/QPay/order_query.php",//提交的URL
		data: { out_trade_no: "{$invoiceid}" },
		dataType:"json",
		async: true,
		success: function (data) {
			// 判断是否成功
			if (data.code == "SUCCESS") {
	            $(".PayIMG").hide();
	            $(".Paytext").html("<i class=\"iconfont icon-chenggong text-success\"></i> 支付成功");
	            setTimeout(function(){ window.location.href="{$returnurl}" }, 2000);
			} else if (data.code == "USERPAYING") {
	            $(".Paytext").html("<i class=\"iconfont icon-dengdai\"></i> 正在等待支付结果<br />请勿关闭当前页面");
			}
		}
	});
}

window.jQuery || document.write("<script src=\"//cdnjs.neworld.org/ajax/libs/jquery/3.1.0/jquery.min.js\"><\/script>");
</script>
<!-- 微信支付 开始 -->
<link href="{$WEB_ROOT}/modules/gateways/QPay/style.css?v1" rel="stylesheet">
<script src="{$WEB_ROOT}/modules/gateways/QPay/qrcode.min.js"></script>
<div class="PayDiv">
    <div class="PayCode" id="PayCode">
    	<div class="PayIMG" id="qrcode">
	    	<img class="WePay" src="{$WEB_ROOT}/modules/gateways/QPay/QPay-icon.png" alt="" width="38" height="38" />
    	</div>
    	<div class="Paytext">
	    	<i class="iconfont icon-saoyisao"></i>
	    	请打开手机QQ<br/>扫一扫继续付款
	    </div>
    </div>
</div>
<script>
(function() {
    var qrcode = new QRCode("qrcode", {
        text: '{$qrcode}',
        width: 400,
        height: 400,
        colorDark : "#000",
        colorLight : "#FFF",
        correctLevel : QRCode.CorrectLevel.L
    });
})();
</script>