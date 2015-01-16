
###Statement
本组件目前只支持手机网站(wap)快捷支付（即时到账）和 pc网站快捷支付(即时到账)。组件改自支付宝接口demo。【版本：3.3 日期：2012-07-23】。目的只是为了方便自己使用，所以没有进行严格测试，只在本人项目中测试通过并使用。在你使用本组件前，强烈建议你先使用支付宝demo测试，并了解支付流程。

###Install

1.将 ```'hardywen/alipay': 'dev-master'``` 加入composer.json文件 (Add ```'hardywen/alipay': 'dev-master'``` to composer.json)

```json
"require": {
	  "laravel/framework": "4.2.*",
	  "..."
	  "hardywen/alipay": "dev-master"
},

```

2.运行```composer install``` 安装本组件 (run ```composer install``` to install this service)

3.在```app/config/app.php```中加入以下配置 (Add below config to ```app/config/app.php```)

```php
	'providers' => array(
	    '...',
	    'Hardywen\Alipay\AlipayServiceProvider',
	)
	
	
	'aliases' => array(
	    '...',
	    'Alipay'            => 'Hardywen\Alipay\Facades\AlipayFacade',
	)
```


###Config

4.运行下面这条命令(Run comment below)

```php artisan config:publish hardy/alipay```

5.运行上面命令后，可以在 ```app/config/packages/hardywen/alipay/config```里配置支付宝的相关参数 （After step 4, you can config your Alipay configurations in  ```app/config/packages/hardywen/alipay/config```）

###Usage

6.支付样例(Payment Example)
```php
$pay = Alipay::instance('web'); // 如果要使用wap支付，则使用 $pay = Alipay::instance('wap')
$config = [
	"notify_url"	=> 'http://xxx.com/notify_url', // 异步通知地址
	"call_back_url"	=> 'http://xxx.com/call_back_url',//前台跳转地址
	"out_trade_no"	=> 'xxxxxxxx', //订单号
	"subject"	=> 'test',
	"total_fee"	=> '0.01',
	//"body"	=> '测试',
	"show_url"	=> 'http://xxx.com/show_url'
];

//setConfig()方法将传入的配置参数与配置文件中的参数合并，
//如果有相同的参数项，配置文件中的配置将会被传入的新配置覆盖。

$form = $pay->setConfig($config)->buildRequestForm(); // 将生成一个支付表单就使用js提交表单, 
// 还提供一个只生成支付链接的方法 buildRequestUrl();
return Response::make($form);
```

7.回调样例(Notify Example)
```php
$pay = Alipay::instance('web');

$notify_result = $pay->verifyNotify();

if($notify == true){
	//$pay->getNotifyData() 方法可以获取回调的notify_data数据。
	//**注意** 只获取 notify_data 字段数据，并非所有回调数据。所有数据你可以使用$_POST或Input::all()获取
	//执行你的业务逻辑，例如更新订单状态，记录支付情况等等
	
	die('success'); // 处理完成后必须返回 success 告诉支付宝。
					//**注意** 只能返回success，不能带有其他东西。
}else{
	//验证失败 执行你的业务逻辑
}
```


