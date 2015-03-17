<?php

return array(


	//wap支付配置
	'wap' => array(

		//合作身份者id，以2088开头的16位纯数字
		'partner' => '',


		'seller_email' => '',


		//签名方式 不需修改
		'sign_type' => '0001',


		//安全检验码，以数字和字母组成的32位字符
		//如果签名方式(sign_type)设置为“MD5”时，请设置该参数
		'key' => '',


		//商户的私钥
		//如果签名方式(sign_type)设置为“0001”时，请设置该参数
		'merchant_private_key' => file_get_contents(__DIR__.'/key/rsa_private_key.pem'), //demo中只是提供路径，本接口直接配置私钥


		//支付宝公钥  **注意** 此为 支付宝 的公钥，并不是商户的公钥
		//如果签名方式(sign_type)设置为“0001”时，请设置该参数
		'alipay_public_key' => file_get_contents(__DIR__.'/key/alipay_public_key.pem'),//demo中只是提供路径，本接口直接配置公钥


		//字符编码格式 目前支持 gbk 或 utf-8
		'input_charset' => 'utf-8',


		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		'cacert' => getcwd().'\\cacert.pem',


		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		'transport' => 'http',


		'format' => 'xml', //不需要改动


		'v' => '2.0',//不需要改动


		//服务器异步通知页面路径
		'notify_url' => '',


		//页面跳转同步通知页面路径
		'call_back_url' => '',


		//操作中断返回地址
		'merchant_url' => '',


		//日志配置
		'log' => true,

	),

	//web支付配置
	'web' => array(

		//合作身份者id，以2088开头的16位纯数字
		'partner' => '',


		'seller_email' => '',


		//签名方式 不需修改
		'sign_type' => 'MD5',


		//安全检验码，以数字和字母组成的32位字符
		//如果签名方式(sign_type)设置为“MD5”时，请设置该参数
		'key' => 'yfbngluafy05qdlruvcn06l4aax19he1',


		//字符编码格式 目前支持 gbk 或 utf-8
		'input_charset' => 'utf-8',


		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		'cacert' => getcwd().'\\cacert.pem',


		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		'transport' => 'http',


		//服务器异步通知页面路径
		'notify_url' => '',


		//页面跳转同步通知页面路径
		'return_url' => '',


		//日志配置
		'log' => true,

	)

);