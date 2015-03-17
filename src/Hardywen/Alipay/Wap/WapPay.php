<?php namespace Hardywen\Alipay\Wap;

use Hardywen\Alipay\Base;
use Illuminate\Support\Facades\Log;


class WapPay extends Base{


	//支付宝网关地址
	var $alipay_gateway_new = 'http://wappaygw.alipay.com/service/rest.htm?';

	var $request_token;


	function __construct($config){
		$this->alipay_config = $config;

		//生成请求ID,每一次支付的请求id不能重复
		$this->alipay_config['req_id'] = $this->createRequestId();
	}



	/**
	 * 获取请求参数
	 * @param $req_data
	 * @param int $service 1：alipay.wap.trade.create.direct， 2：alipay.wap.auth.authAndExecute
	 * @return array
	 */
	protected function getRequestParameter($req_data,$service = 1){
		$param = array(
			"service"   => $service === 1 ? "alipay.wap.trade.create.direct" : "alipay.wap.auth.authAndExecute",
			"partner"   => trim($this->alipay_config['partner']),
			"sec_id"    => strtoupper(trim($this->alipay_config['sign_type'])),
			"format"	=> $this->alipay_config['format'],
			"v"	        => $this->alipay_config['v'],
			"req_id"	=> $this->alipay_config['req_id'],
			"req_data"	=> $req_data,
			"_input_charset"    => trim(strtolower($this->alipay_config['input_charset']))
		);

		return $param;
	}
	/**
	 * 获取token值
	 * @return mixed 返回请求token值
	 */
	protected function getRequestToken(){
		if($this->request_token)
			return $this->request_token;

		$req_data = '<direct_trade_create_req><notify_url>' . $this->alipay_config['notify_url']
			. '</notify_url><call_back_url>'. $this->alipay_config['call_back_url']
			. '</call_back_url><seller_account_name>' . $this->alipay_config['seller_email']
			. '</seller_account_name><out_trade_no>' . $this->alipay_config['out_trade_no']
			. '</out_trade_no><subject>' . $this->alipay_config['subject']
			. '</subject><total_fee>' . $this->alipay_config['total_fee']
			. '</total_fee><merchant_url>' . $this->alipay_config['merchant_url']
			. '</merchant_url></direct_trade_create_req>';

		$param = $this->getRequestParameter($req_data);

		$html_text = $this->buildRequestHttp($param);

		//Url Decode返回的信息
		$html_text = urldecode($html_text);

		//解析远程模拟提交后返回的信息
		$para_html_text = $this->parseResponse($html_text);

		if(isset($para_html_text['res_error'])){
			$error = simplexml_load_string($para_html_text['res_error']);
			throw new \RuntimeException($error->msg.': '.$error->detail);
		}

		//获取request_token
		$request_token = $para_html_text['request_token'];

		$this->request_token = $request_token;

		return $this->request_token;
	}



	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $method 提交方式。两个值可选：post、get
	 * @param $button_name 确认按钮显示文字
	 * @return 提交表单HTML文本
	 */
	public function buildRequestForm($method = 'get', $button_name = 'submit',$target = '_self') {

		$request_token = $this->getRequestToken();

		//业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';

		//构造要请求的参数数组
		$para_temp = $this->getRequestParameter($req_data,2);

		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);

		$sHtml = '<h1>正在跳转到支付宝...</h1>';

		$sHtml .= "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset="
			.trim(strtolower($this->alipay_config['input_charset']))."' method='".$method."' target='".$target."'>";
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

		$request_token = $this->getRequestToken();

		//业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';

		//构造要请求的参数数组
		$para_temp = $this->getRequestParameter($req_data,2);

		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);

		$url = $this->alipay_gateway_new;
		$url .= $this->createLinkStringUrlEncode($para);

		return $url;
	}



	/**
	 * 解析远程模拟提交后返回的信息
	 * @param $str_text 要解析的字符串
	 * @return 解析结果
	 */
	protected function parseResponse($str_text) {
		//以“&”字符切割字符串
		$para_split = explode('&',$str_text);

		//把切割后的字符串数组变成变量与数值组合的数组
		foreach ($para_split as $item) {
			//获得第一个=字符的位置
			$nPos = strpos($item,'=');
			//获得字符串长度
			$nLen = strlen($item);
			//获得变量名
			$key = substr($item,0,$nPos);
			//获得数值
			$value = substr($item,$nPos+1,$nLen-$nPos-1);
			//放入数组中
			$para_text[$key] = $value;
		}

		if( ! empty ($para_text['res_data'])) {
			//解析加密部分字符串
			if($this->alipay_config['sign_type'] == '0001') {
				$para_text['res_data'] = $this->rsaDecrypt($para_text['res_data'], $this->alipay_config['merchant_private_key']);
			}

			//token从res_data中解析出来（也就是说res_data中已经包含token的内容）
			$doc = new \DOMDocument();
			$doc->loadXML($para_text['res_data']);
			$para_text['request_token'] = $doc->getElementsByTagName( "request_token" )->item(0)->nodeValue;
		}

		return $para_text;
	}



	/**
	 * notify functions =========================================================================
	 */

	/**
	 * 针对notify_url验证消息是否是支付宝发出的合法消息
	 * @return 验证结果
	 */
	public function verifyNotify(){
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {

			//对notify_data解密
			$decrypt_post_para = $_POST;
			if ($this->alipay_config['sign_type'] == '0001') {
				$decrypt_post_para['notify_data'] = $this->rsaDecrypt($decrypt_post_para['notify_data'],
					$this->alipay_config['merchant_private_key']);
			}

			//notify_id从decrypt_post_para中解析出来（也就是说decrypt_post_para中已经包含notify_id的内容）
			$doc = new \DOMDocument();
			$doc->loadXML($decrypt_post_para['notify_data']);
			$notify_id = $doc->getElementsByTagName( "notify_id" )->item(0)->nodeValue;

			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($notify_id)) {$responseTxt = $this->getResponse($notify_id);}

			//生成签名结果
			$isSign = $this->getSignVerify($decrypt_post_para, $_POST["sign"],false);

			//写日志记录
			if($this->alipay_config['log'] || true) {
				if ($isSign) {
					$isSignStr = 'true';
				} else {
					$isSignStr = 'false';
				}
				$log_text = "[===AliPay Notify===]responseTxt=" . $responseTxt . "\n notify_url_log:isSign=" .$isSignStr. "\n";
				$log_text = $log_text . $this->createLinkString($_POST);
				Log::info($log_text);
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


	/**
	 * 获取通知业务参数
	 * @return 通知业务参数
	 */
	public function getNotifyData(){
		$data = $_POST;
		$content = $data['notify_data'];

		if($data['sec_id'] === '0001' || $data['sec_id'] === 'RSA'){
			$content = $this->decrypt($content);
		}

		$content = simplexml_load_string($content);

		return $content;

	}

	/**
	 * 解密
	 * @param $prestr 要解密数据
	 * @return 解密后结果
	 */
	public function decrypt($prestr) {
		return $this->rsaDecrypt($prestr, trim($this->alipay_config['merchant_private_key']));
	}

	/**
	 * 异步通知时，对参数做固定排序
	 * @param $para 排序前的参数组
	 * @return 排序后的参数组
	 */
	protected function sortNotifyPara($para) {
		$para_sort['service'] = $para['service'];
		$para_sort['v'] = $para['v'];
		$para_sort['sec_id'] = $para['sec_id'];
		$para_sort['notify_data'] = $para['notify_data'];

		return $para_sort;
	}



}