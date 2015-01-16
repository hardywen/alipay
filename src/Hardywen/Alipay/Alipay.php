<?php namespace Hardywen\Alipay;

use Hardywen\Alipay\Wap\WapPay;
use Hardywen\Alipay\Web\WebPay;


class Alipay {

	public $config;

	function __construct($config){
		$this->config = $config;
	}

	public function instance($type){

		switch($type){
			case 'wap':
				return new WapPay($this->config['wap']);
			break;
			case 'web':
				return new WebPay($this->config['web']);
			default:
				return false;
			break;
		}
	}
}