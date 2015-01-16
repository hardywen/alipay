<?php namespace Hardywen\Alipay\lib;

trait RsaFunctions
{
	/* *
	 * 支付宝接口RSA函数
	 * 详细：RSA签名、验签、解密
	 * 版本：3.3
	 * 日期：2012-07-23
	 * 说明：
	 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
	 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
	 */

	/**
	 * RSA签名
	 * @param $data 待签名数据
	 * @param $private_key 商户私钥文件路径
	 * @return 签名结果
	 */
	public function rsaSign($data, $private_key)
	{
		$res = openssl_get_privatekey($private_key);
		openssl_sign($data, $sign, $res);
		openssl_free_key($res);
		//base64编码
		$sign = base64_encode($sign);
		return $sign;
	}

	/**
	 * RSA验签
	 * @param $data 待签名数据
	 * @param $public_key 支付宝的公钥文件路径
	 * @param $sign 要校对的的签名结果
	 * @return 验证结果
	 */
	public function rsaVerify($data, $public_key, $sign)
	{
		$res = openssl_get_publickey($public_key);
		$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		openssl_free_key($res);
		return $result;
	}

	/**
	 * RSA解密
	 * @param $content 需要解密的内容，密文
	 * @param $private_key 商户私钥文件路径
	 * @return 解密后内容，明文
	 */
	public function rsaDecrypt($content, $private_key)
	{
		$res = openssl_get_privatekey($private_key);
		//用base64将内容还原成二进制
		$content = base64_decode($content);
		//把需要解密的内容，按128位拆开解密
		$result = '';
		for ($i = 0; $i < strlen($content) / 128; $i++) {
			$data = substr($content, $i * 128, 128);
			openssl_private_decrypt($data, $decrypt, $res);
			$result .= $decrypt;
		}
		openssl_free_key($res);
		return $result;
	}
}