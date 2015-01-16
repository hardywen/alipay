<?php namespace Hardywen\Alipay\Facades;

use Illuminate\Support\Facades\Facade;

class AlipayFacade extends Facade {

	protected static function getFacadeAccessor() {
		return 'alipay';
	}
}