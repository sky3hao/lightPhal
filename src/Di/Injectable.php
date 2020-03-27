<?php

namespace Tengyue\Infra\Di;

use Tengyue\Infra\Di;
use Tengyue\Infra\DiInterface;
use Tengyue\Infra\Di\InjectionAwareInterface;
use Tengyue\Infra\Di\Exception;

/**
 * Tengyue\Infra\Di\Injectable
 *
 * This class allows to access services in the services container by just only accessing a public property
 * with the same name of a registered service
 *
 * @property \Tengyue\Infra\Logger|\Monolog\Logger $logger
 * @property \Tengyue\Infra\Config|\Tengyue\Infra\Config\Factory $config
 * @property \Tengyue\Infra\Http\Request $request
 * @property \Tengyue\Infra\Http\Response $response
 * @property \Tengyue\Infra\Queue\MNS\MNS\Producer $mnsProducer
 * @property \Tengyue\Infra\Queue\MNS\MNS\Consumer $mnsConsumer
 * @property \Tengyue\Infra\Etcd\Client $etcdClient
 * @property \Tengyue\Infra\Cache\Backend\Redis $cache
 * @property \Tengyue\Infra\Redis\Redis|\Redis $redis
 */
abstract class Injectable implements InjectionAwareInterface
{

	/**
	 * Dependency Injector
	 *
	 * @$\Tengyue\Infra\DiInterface
	 */
	protected $_dependencyInjector;

	/**
	 * Sets the dependency injector
	 */
	public function setDI(DiInterface $dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the internal dependency injector
	 */
	public function getDI()
	{
		$dependencyInjector = $this->_dependencyInjector;
		if (!is_object($dependencyInjector)) {
			$dependencyInjector = Di::getDefault();
		}
		return $dependencyInjector;
	}

	/**
	 * Magic method __get
	 */
	public function __get($propertyName)
	{
		$dependencyInjector = $this->_dependencyInjector;
		if (!is_object($dependencyInjector)) {
			$dependencyInjector = Di::getDefault();
			if (!is_object($dependencyInjector)) {
				throw new Exception("A dependency injection object is required to access the application services");
			}
		}

		/**
		 * Fallback to the PHP userland if the cache is not available
		 */
		if ($dependencyInjector->has($propertyName)) {
			$service = $dependencyInjector->getShared($propertyName);
			$this->{$propertyName} = $service;
			return $service;
		}

		if ($propertyName == "di") {
			$this->{"di"} = $dependencyInjector;
			return $dependencyInjector;
		}

		/**
		 * A notice is shown if the property is not defined and isn't a valid service
		 */
		trigger_error("Access to undefined property " . $propertyName);
		return null;
	}
}
