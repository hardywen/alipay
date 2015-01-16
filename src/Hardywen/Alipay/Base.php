<?php namespace Hardywen\Alipay;

use Hardywen\Alipay\lib\CoreFunctions;
use Hardywen\Alipay\lib\Md5Functions;
use Hardywen\Alipay\lib\RsaFunctions;
use Log;

class Base {

	use CoreFunctions,RsaFunctions,Md5Functions;

	//支付宝基础配置
	var $alipay_config;

	//支付宝网关地址
	var $alipay_gateway_new = 'http://wappaygw.alipay.com/service/rest.htm?';

	//HTTPS形式消息验证地址
	var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

	//HTTP形式消息验证地址
	var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';



	function __construct($config){
		$this->alipay_config = $config;
	}

	/**
	 * 默认读取配置文件中的配置，如有需要可以使用此方法覆盖原有配置或新增配置项。
	 * @param $config 新配置
	 * @return $this 返回自身
	 */
	public function setConfig($config){
		$this->alipay_config = array_merge($this->alipay_config,$config);

		return $this;
	}



	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * @return 签名结果字符串
	 */
	protected function buildRequestMysign($para_sort) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkString($para_sort);

		$mysign = "";
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "MD5" :
				$mysign = $this->md5Sign($prestr, $this->alipay_config['key']);
				break;
			case "RSA" :
				$mysign = $this->rsaSign($prestr, $this->alipay_config['merchant_private_key']);
				break;
			case "0001" :
				$mysign = $this->rsaSign($prestr, $this->alipay_config['merchant_private_key']);
				break;
			default :
				$mysign = "";
		}

		return $mysign;
	}

	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	protected function buildRequestPara($para_temp) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildRequestMysign($para_sort);

		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		if($para_sort['service'] != 'alipay.wap.trade.create.direct' && $para_sort['service'] != 'alipay.wap.auth.authAndExecute') {
			$para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
		}

		return $para_sort;
	}

	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组字符串
	 */
	protected function buildRequestParaToString($para_temp) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);

		//把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
		$request_data = $this->createLinkStringUrlEncode($para);

		return $request_data;
	}


	/**
	 * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
	 * @param $para_temp 请求参数数组
	 * @return 支付宝处理结果
	 */
	protected function buildRequestHttp($para_temp) {
		$sResult = '';

		//待请求参数数组字符串
		$request_data = $this->buildRequestPara($para_temp);

		//远程获取数据
		$sResult = $this->getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'],$request_data,trim(strtolower($this->alipay_config['input_charset'])));

		return $sResult;
	}

	/**
	 * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果，带文件上传功能
	 * @param $para_temp 请求参数数组
	 * @param $file_para_name 文件类型的参数名
	 * @param $file_name 文件完整绝对路径
	 * @return 支付宝返回处理结果
	 */
	protected function buildRequestHttpInFile($para_temp, $file_para_name, $file_name) {

		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		$para[$file_para_name] = "@".$file_name;

		//远程获取数据
		$sResult = $this->getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'],$para,trim(strtolower($this->alipay_config['input_charset'])));

		return $sResult;
	}

	/**
	 * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
	 * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
	 * @return 时间戳字符串
	 */
	protected function query_timestamp() {
		$url = $this->alipay_gateway_new."service=query_timestamp&partner=".trim(strtolower($this->alipay_config['partner']))."&_input_charset=".trim(strtolower($this->alipay_config['input_charset']));
		$encrypt_key = "";

		$doc = new \DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName( "encrypt_key" );
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;

		return $encrypt_key;
	}



	/**
	 * notify functions =========================================================================
	 */


	/**
	 * 针对return_url验证消息是否是支付宝发出的合法消息
	 * @return 验证结果
	 */
	public function verifyReturn(){
		if(empty($_GET)) {//判断GET来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVerify($_GET, $_GET["sign"],true);

			//写日志记录
			if($this->alipay_config['log'] || true) {
				if ($isSign) {
					$isSignStr = 'true';
				} else {
					$isSignStr = 'false';
				}
				$log_text = "[===AliPay Return===]return_url_log:isSign=" . $isSignStr . "\n";
				$log_text = $log_text . $this->createLinkString($_GET);
				Log::error($log_text);
			}

			//验证
			//$responseTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if ($isSign) {
				return true;
			} else {
				return false;
			}
		}
	}


	/**
	 * 获取返回时的签名验证结果
	 * @param $para_temp 通知返回来的参数数组
	 * @param $sign 返回的签名结果
	 * @param $isSort 是否对待签名数组排序
	 * @return 签名验证结果
	 */
	protected function getSignVerify($para_temp, $sign, $isSort) {
		//除去待签名参数数组中的空值和签名参数
		$para = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		if($isSort) {
			$para = $this->argSort($para);
		} else {
			$para = $this->sortNotifyPara($para);
		}

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkString($para);

		$isSign = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "MD5" :
				$isSign = $this->md5Verify($prestr, $sign, $this->alipay_config['key']);
				break;
			case "RSA" :
				$isSign = $this->rsaVerify($prestr, trim($this->alipay_config['alipay_private_key']), $sign);
				break;
			case "0001" :
				$isSign = $this->rsaVerify($prestr, trim($this->alipay_config['alipay_private_key']), $sign);
				break;
			default :
				$isSign = false;
		}

		return $isSign;
	}

	/**
	 * 获取远程服务器ATN结果,验证返回URL
	 * @param $notify_id 通知校验ID
	 * @return 服务器ATN结果
	 * 验证结果集：
	 * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
	 * true 返回正确信息
	 * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	 */
	protected function getResponse($notify_id) {
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$verify_url = '';
		if($transport == 'https') {
			$verify_url = $this->https_verify_url;
		}
		else {
			$verify_url = $this->http_verify_url;
		}
		$verify_url = $verify_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = $this->getHttpResponseGET($verify_url, $this->alipay_config['cacert']);

		return $responseTxt;
	}


}