<?php

namespace Tengyue\Infra\Di;

/**
 * Tengyue\Infra\Di\FactoryDefault
 *
 * This is a variant of the standard Tengyue\Infra\Di. By default it automatically
 * registers all the services provided by the framework. Thanks to this, the developer does not need
 * to register each service individually providing a full stack framework
 */
class FactoryDefault extends \Tengyue\Infra\Di
{
	/**
	 * Tengyue\Infra\Di\FactoryDefault constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_services = [
			"response"      => new Service("response", "Tengyue\\Infra\\Http\\Response", true),
			"request"       => new Service("request", "Tengyue\\Infra\\Http\\Request", true),
			"filter"        => new Service("filter", "Tengyue\\Infra\\Filter", true),
			//"security"      => new Service("security", "Tengyue\\Infra\\Security", true),
			"crypt"         => new Service("crypt", "Tengyue\\Infra\\Crypt", true),
		];
	}
}
