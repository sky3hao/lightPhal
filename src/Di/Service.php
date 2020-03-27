<?php

namespace Tengyue\Infra\Di;

use Tengyue\Infra\DiInterface;
use Tengyue\Infra\Di\Exception;
use Tengyue\Infra\Di\ServiceInterface;
use Tengyue\Infra\Di\Service\Builder;

/**
 * Tengyue\Infra\Di\Service
 *
 * Represents individually a service in the services container
 *
 *<code>
 * $service = new \Tengyue\Infra\Di\Service(
 *     "request",
 *     "Tengyue\Infra\Http\\Request"
 * );
 *
 * $request = service->resolve();
 *</code>
 */
class Service implements ServiceInterface
{

	protected $_name;

	protected $_definition;

	protected $_shared = false;

	protected $_resolved = false;

	protected $_sharedInstance;

	/**
	 * Tengyue\Infra\Di\Service
	 *
	 * @param $name
	 * @param mixed definition
	 * @param $shared
	 */
	public final function __construct($name, $definition, $shared = false)
	{
		$this->_name = $name;
		$this->_definition = $definition;
		$this->_shared = $shared;
	}

	/**
	 * Returns the service's name
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Sets if the service is shared or not
	 */
	public function setShared($shared)
	{
		$this->_shared = $shared;
	}

	/**
	 * Check whether the service is shared or not
	 */
	public function isShared()
	{
		return $this->_shared;
	}

	/**
	 * Sets/Resets the shared instance related to the service
	 *
	 * @param mixed sharedInstance
	 */
	public function setSharedInstance($sharedInstance)
	{
		$this->_sharedInstance = $sharedInstance;
	}

	/**
	 * Set the service definition
	 *
	 * @param mixed definition
	 */
	public function setDefinition($definition)
	{
		$this->_definition = $definition;
	}

	/**
	 * Returns the service definition
	 *
	 * @return mixed
	 */
	public function getDefinition()
	{
		return $this->_definition;
	}

	/**
	 * Resolves the service
	 *
	 * @param array parameters
	 * @param \Tengyue\Infra\DiInterface dependencyInjector
	 * @return mixed
	 */
	public function resolve($parameters = null, DiInterface $dependencyInjector = null)
	{
		$shared = $this->_shared;

		/**
		 * Check if the service is shared
		 */
		if ($shared) {
			$sharedInstance = $this->_sharedInstance;
			if ($sharedInstance !== null) {
				return $sharedInstance;
			}
		}

		$found = true;
		$instance = null;

		$definition = $this->_definition;
		if (is_string($definition)) {

			/**
			 * $definitions can be class names without implicit parameters
			 */
			if (class_exists($definition)) {
				if (is_array($parameters)) {
					if (count($parameters)) {
                        $instance = new $definition(...$parameters);
					} else {
						$instance = new $definition();
					}
				} else {
					$instance = new $definition();
				}
			} else {
				$found = false;
			}
		} else {

			/**
			 * Object definitions can be a Closure or an already resolved instance
			 */
			if (is_object($definition)) {
				if ($definition instanceof \Closure) {

					/**
					 * Bounds the closure to the current DI
					 */
					if (is_object($dependencyInjector)) {
						$definition = \Closure::bind($definition, $dependencyInjector);
					}

					if (is_array($parameters)) {
						$instance = call_user_func_array($definition, $parameters);
					} else {
						$instance = call_user_func($definition);
					}
				} else {
					$instance = $definition;
				}
			} else {
				/**
				 * Array definitions require a 'className' parameter
				 */
				if (is_array($definition)) {
					$builder = new Builder();
					$instance = $builder->build($dependencyInjector, $definition, $parameters);
				} else {
					$found = false;
				}
			}
		}

		/**
		 * If the service can't be built, we must throw an exception
		 */
		if ($found === false)  {
			throw new Exception("Service '" . $this->_name . "' cannot be resolved");
		}

		/**
		 * Update the shared instance if the service is shared
		 */
		if ($shared) {
			$this->_sharedInstance = $instance;
		}

		$this->_resolved = true;

		return $instance;
	}

	/**
	 * Changes a parameter in the definition without resolve the service
	 */
	public function setParameter($position, $parameter)
	{
		$definition = $this->_definition;
		if (!is_array($definition)) {
			throw new Exception("Definition must be an array to update its parameters");
		}

		/**
		 * Update the parameter
		 */
		if (isset($definition["arguments"])) {
			$arguments = $definition["arguments"];
			$arguments[$position] = $parameter;
		} else {
			$arguments = [$position => $parameter];
		}

		/**
		 * Re-update the arguments
		 */
		$definition["arguments"] = $arguments;

		/**
		 * Re-update the definition
		 */
		$this->_definition = $definition;

		return $this;
	}

	/**
	 * Returns a parameter in a specific position
	 *
	 * @param int position
	 * @return array
	 */
	public function getParameter($position)
	{
		$definition = $this->_definition;
		if (!is_array($definition)) {
			throw new Exception("Definition must be an array to obtain its parameters");
		}

		/**
		 * Update the parameter
		 */
		if (isset($definition["arguments"])) {
			$arguments = $definition["arguments"];
			if (isset($arguments[$position])) {
				return $arguments[$position];
			}
		}

		return null;
	}

	/**
	 * Returns true if the service was resolved
	 */
	public function isResolved()
	{
		return $this->_resolved;
	}

	/**
	 * Restore the internal state of a service
	 */
	public static function __set_state($attributes)
	{
		if (!isset($attributes["_name"])) {
			throw new Exception("The attribute '_name' is required");
		}
		$name = $attributes["_name"];

		if (!isset($attributes["_definition"])) {
			throw new Exception("The attribute '_definition' is required");
		}
		$definition = $attributes["_definition"];

		if (!isset($attributes["_shared"])) {
			throw new Exception("The attribute '_shared' is required");
		}
		$shared = $attributes["_shared"];

		return new self($name, $definition, $shared);
	}
}
