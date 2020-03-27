<?php

namespace Tengyue\Infra;

use Tengyue\Infra\Config;
use Tengyue\Infra\Di\Service;
use Tengyue\Infra\DiInterface;
use Tengyue\Infra\Di\Exception;
use Tengyue\Infra\Config\Adapter\Php;
use Tengyue\Infra\Config\Adapter\Yaml;
use Tengyue\Infra\Di\ServiceInterface;
use Tengyue\Infra\Di\InjectionAwareInterface;
use Tengyue\Infra\Di\ServiceProviderInterface;
use Tengyue\Infra\Helper\Common;

/**
 * Tengyue\Infra\Di
 *
 *<code>
 * use Tengyue\Infra\Di;
 *
 * $di = new Di();
 *
 * // Using a $definition
 * $di->set("request", Request::class, true);
 *
 * // Using an anonymous function
 * $di->setShared(
 *     "request",
 *     function () {
 *         return new Request();
 *     }
 * );
 *
 * $request = $di->getRequest();
 *</code>
 */
class Di implements DiInterface
{
	/**
	 * List of registered services
	 */
	protected $_services;

	/**
	 * List of shared instances
	 */
	protected $_sharedInstances;

	/**
	 * To know if the latest resolved instance was shared or not
	 */
	protected $_freshInstance = false;

	/**
	 * Latest DI build
	 */
	protected static $_default;

	/**
	 * Tengyue\Infra\Di constructor
	 */
	public function __construct()
	{
		$di = self::$_default;
		if (!$di) {
			self::$_default = $this;
		}
	}


	/**
	 * Registers a service in the services container
	 */
	public function set($name, $definition, $shared = false)
	{
		$service = new Service($name, $definition, $shared);
		$this->_services[$name] = $service;
		return $service;
	}

	/**
	 * Registers an "always shared" service in the services container
	 */
	public function setShared($name, $definition)
	{
		return $this->set($name, $definition, true);
	}

	/**
	 * Removes a service in the services container
	 * It also removes any shared instance created for the service
	 */
	public function remove($name)
	{
		unset($this->_services[$name]);
		unset($this->_sharedInstances[$name]);
	}

	/**
	 * Attempts to register a service in the services container
	 * Only is successful if a service hasn't been registered previously
	 * with the same name
	 */
	public function attempt($name, $definition, $shared = false)
	{
		if (!isset($this->_services[$name])) {
			$service = new Service($name, $definition, $shared);
			$this->_services[$name] = $service;
			return $service;
		}

		return false;
	}

	/**
	 * Sets a service using a raw Tengyue\Infra\Di\Service definition
	 */
	public function setRaw($name, ServiceInterface $rawDefinition)
	{
		$this->_services[$name] = $rawDefinition;
		return $rawDefinition;
	}

	/**
	 * Returns a service definition without resolving
	 */
	public function getRaw($name)
	{
		if (isset($this->_services[$name])) {
			return $this->_services[$name]->getDefinition();
		}

		throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
	}

	/**
	 * Returns a Tengyue\Infra\Di\Service instance
	 */
	public function getService($name)
	{
		if (isset($this->_services[$name])) {
			return $this->_services[$name];
		}

		throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
	}

	/**
	 * Resolves the service based on its configuration
	 */
	public function get($name, $parameters = null)
	{
		$instance = null;

		if (!is_object($instance)) {
			if (isset($this->_services[$name])) {
				/**
				 * The service is registered in the DI
				 */
				$instance = $this->_services[$name]->resolve($parameters, $this);
			} else {
				/**
				 * The DI also acts as builder for any class even if it isn't defined in the DI
				 */
				if (!class_exists($name)) {
					throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
				}

				if (is_array($parameters) && count($parameters)) {
                    $instance = new $name(...$parameters);
				} else {
					$instance = new $name();
				}
			}
		}

		/**
		 * Pass the DI itself if the instance implements \Tengyue\Infra\Di\InjectionAwareInterface
		 */
		if (is_object($instance)) {
			if ($instance instanceof InjectionAwareInterface) {
				$instance->setDI($this);
			}
		}

		return $instance;
	}

	/**
	 * Resolves a service, the resolved service is stored in the DI, subsequent
	 * requests for this service will return the same instance
	 *
	 * @param $name
	 * @param array $parameters
	 * @return mixed
	 */
	public function getShared($name, $parameters = null)
	{
		/**
		 * This method provides a first level to shared instances allowing to use non-shared services as shared
		 */
		if (isset($this->_sharedInstances[$name])) {
			$instance = $this->_sharedInstances[$name];
			$this->_freshInstance = false;
		} else {

			/**
			 * Resolve the instance normally
			 */
			$instance = $this->get($name, $parameters);

			/**
			 * Save the instance in the first level shared
			 */
			$this->_sharedInstances[$name] = $instance;
			$this->_freshInstance = true;
		}

		return $instance;
	}

	/**
	 * Check whether the DI contains a service by a name
	 */
	public function has($name)
	{
		return isset($this->_services[$name]);
	}

	/**
	 * Check whether the last service obtained via getShared produced a fresh instance or an existing one
	 */
	public function wasFreshInstance()
	{
		return $this->_freshInstance;
	}

	/**
	 * Return the services registered in the DI
	 */
	public function getServices()
	{
		return $this->_services;
	}

	/**
	 * Check if a service is registered using the array syntax
	 */
	public function offsetExists($name)
	{
		return $this->has($name);
	}

	/**
	 * Allows to register a shared service using the array syntax
	 *
	 *<code>
	 * $di["request"] = new \Tengyue\Infra\Http\Request();
	 *</code>
	 */
	public function offsetSet($name, $definition)
	{
		$this->setShared($name, $definition);
		return true;
	}

	/**
	 * Allows to obtain a shared service using the array syntax
	 *
	 *<code>
	 * var_dump($di["request"]);
	 *</code>
	 */
	public function offsetGet($name)
	{
		return $this->getShared($name);
	}

	/**
	 * Removes a service from the services container using the array syntax
	 */
	public function offsetUnset($name)
	{
		return false;
	}

	/**
	 * Magic method to get or set services using setters/getters
	 */
	public function __call($method, $arguments = null)
	{
		/**
		 * If the magic method starts with "get" we try to get a service with that name
		 */
		if (Common::startsWith($method, "get")) {
			$services = $this->_services;
			$possibleService = lcfirst(substr($method, 3));
			if (isset($services[$possibleService])) {
				if (count($arguments)) {
					$instance = $this->get($possibleService, $arguments);
				} else {
					$instance = $this->get($possibleService);
				}
				return $instance;
			}
		}

		/**
		 * If the magic method starts with "set" we try to set a service using that name
		 */
		if (Common::startsWith($method, "set")) {
			if (isset($arguments[0])) {
				$this->set(lcfirst(substr($method, 3)), $arguments[0]);
				return null;
			}
		}

		/**
		 * The method doesn't start with set/get throw an exception
		 */
		throw new Exception("Call to undefined method or service '" . $method . "'");
	}

	/**
	 * Registers a service provider.
	 *
	 * <code>
	 * use Tengyue\Infra\DiInterface;
	 * use Tengyue\Infra\Di\ServiceProviderInterface;
	 *
	 * class SomeServiceProvider implements ServiceProviderInterface
	 * {
	 *     public function register(DiInterface $di)
	 *     {
	 *         $di->setShared('service', function () {
	 *             // ...
	 *         });
	 *     }
	 * }
	 * </code>
	 */
	public function register(ServiceProviderInterface $provider)
	{
		$provider->register($this);
	}

	/**
	 * Set a default dependency injection container to be obtained into static methods
	 */
	public static function setDefault(DiInterface $dependencyInjector)
	{
		self::$_default = $dependencyInjector;
	}

	/**
	 * Return the latest DI created
	 */
	public static function getDefault()
	{
		return self::$_default;
	}

	/**
	 * Resets the internal default DI
	 */
	public static function reset()
	{
		self::$_default = null;
	}

	/**
	 * Loads services from a yaml file.
	 *
	 * <code>
	 * $di->loadFromYaml(
	 *     "path/services.yaml",
	 *     [
	 *         "!approot" => function ($value) {
	 *             return dirname(__DIR__) . $value;
	 *         }
	 *     ]
	 * );
	 * </code>
	 *
	 * And the services can be specified in the file as:
	 *
	 * <code>
	 * myComponent:
	 *     className: \Acme\Components\MyComponent
	 *     shared: true
	 *
	 * group:
	 *     className: \Acme\Group
	 *     arguments:
	 *         - type: service
	 *           name: myComponent
	 *
	 * user:
	 *    className: \Acme\User
	 * </code>
	 *
	 */
	public function loadFromYaml($filePath, $callbacks = null)
	{
		$services = new Yaml($filePath, $callbacks);

		$this->loadFromConfig($services);
	}

	/**
	 * Loads services from a php config file.
	 *
	 * <code>
	 * $di->loadFromPhp("path/services.php");
	 * </code>
	 *
	 * And the services can be specified in the file as:
	 *
	 * <code>
	 * return [
	 *      'myComponent' => [
	 *          'className' => '\Acme\Components\MyComponent',
	 *          'shared' => true,
	 *      ],
	 *      'group' => [
	 *          'className' => '\Acme\Group',
	 *          'arguments' => [
	 *              [
	 *                  'type' => 'service',
	 *                  'service' => 'myComponent',
	 *              ],
	 *          ],
	 *      ],
	 *      'user' => [
	 *          'className' => '\Acme\User',
	 *      ],
	 * ];
	 * </code>
	 *
	 */
	public function loadFromPhp($filePath)
	{
		$services = new Php($filePath);

		$this->loadFromConfig($services);
	}

	/**
	 * Loads services from a Config object.
	 */
	protected function loadFromConfig(Config $config)
	{
		$services = $config->toArray();

		foreach ($services as $name => $service) {
			$this->set($name, $service, (isset($service["shared"]) && $service["shared"]));
		}
	}
}
