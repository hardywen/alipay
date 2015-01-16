###Install

1.将 ```'hardywen/alipay': 'dev-master'``` 加入composer.json文件 (Insert ```'hardywen/alipay': 'dev-master'``` into composer.json)

```json
"require": {
	  "laravel/framework": "4.2.*",
	  "..."
	  "hardywen/alipay": "dev-master"
},

```

2. 运行```composer install``` 安装本组件 (run ```composer install``` to install this service)

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

6.使用wap支付方式支付()
```php
$wap = Alipay::instance('web');
$config = [
	"notify_url"	=> 'http://xxx.com/notify_url', // 异步通知地址
	"call_back_url"	=> 'http://xxx.com/call_back_url',//前台跳转地址
	"out_trade_no"	=> 'xxxxxxxx', //订单号
	"subject"	=> 'test',
	"total_fee"	=> '0.01',
	//"body"	=> '测试',
	"show_url"	=> 'http://m.bancaiyi.com:8000/products'
];

$form = $wap->setConfig($config)->buildRequestForm();
return Response::make($form);
```
