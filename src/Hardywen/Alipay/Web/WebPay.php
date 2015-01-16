<?php namespace Hardywen\Alipay\Web;

use Hardywen\Alipay\Base;

class WebPay extends Base{

	/**
	 *支付宝网关地址（新）
	 */
	var $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';


	function __construct($alipay_config){
		$this->alipay_config = array_merge([
			"body"	            => '',
			"show_url"	        => '',
			"anti_phishing_key"	=> '',
			"exter_invoke_ip"	=> ''
		],$alipay_config);
	}


	function getParameter(){
		$param = [
			"service"           => "create_direct_pay_by_user",
			"partner"           => trim($this->alipay_config['partner']),
			"payment_type"	    => '1',
			"notify_url"	    => $this->alipay_config['notify_url'],
			"return_url"	    => $this->alipay_config['return_url'],
			"seller_email"	    => $this->alipay_config['seller_email'],
			"out_trade_no"	    => $this->alipay_config['out_trade_no'],
			"subject"	        => $this->alipay_config['subject'],
			"total_fee"	        => $this->alipay_config['total_fee'],
			"body"	            => $this->alipay_config['body'],
			"show_url"	        => $this->alipay_config['show_url'],
			"anti_phishing_key"	=> $this->alipay_config['anti_phishing_key'],
			"exter_invoke_ip"	=> $this->alipay_config['exter_invoke_ip'],
			"_input_charset"	=> trim(strtolower($this->alipay_config['input_charset']))
		];

		return $param;
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $method 提交方式。两个值可选：post、get
	 * @param $button_name 确认按钮显示文字
	 * @return 提交表单HTML文本
	 */
	function buildRequestForm($method = 'get', $button_name = 'submit') {

		$para_temp = $this->getParameter();

		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);

		$sHtml = '<h1>正在跳转到支付宝...</h1>';

		$sHtml .= "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset="
			.trim(strtolower($this->alipay_config['input_charset']))."' method='".$method."'>";
		while (list ($key, $val) = each ($para)) {
			$sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
		}

		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml."<input type='submit' style='display:none' value='".$button_name."'></form>";

		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

		return $sHtml;
	}


	/**
	 * 生成经过url encode的支付链接地址
	 * @return string 返回生成的支付链接
	 */
	public function buildRequestUrl(){

		$para_temp = $this->getParameter();

		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);

		$url = $this->alipay_gateway_new;
		$url .= $this->createLinkStringUrlEncode($para);

		return $url;
	}



	/**
	 * notify functions ===============================================================
	 */

	/**
	 * 针对notify_url验证消息是否是支付宝发出的合法消息
	 * @return 验证结果
	 */
	function verifyNotify(){
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}

			//写日志记录
			if($this->alipay_config['log'] || true) {
				if ($isSign) {
					$isSignStr = 'true';
				} else {
					$isSignStr = 'false';
				}
				$log_text = "[===AliPay Notify===]responseTxt=" . $responseTxt . "\n notify_url_log:isSign=" .$isSignStr. "\n";
				$log_text = $log_text . $this->createLinkString($_POST);
				Log::error($log_text);
			}


			//验证
			//$responseTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}


}