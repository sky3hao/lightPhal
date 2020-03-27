<?php

namespace Tengyue\Infra\Di\Service;

use Tengyue\Infra\DiInterface;
use Tengyue\Infra\Di\Exception;

/**
 * Tengyue\Infra\Di\Service\Builder
 *
 * This class builds instances based on complex definitions
 */
class Builder
{

	/**
	 * Resolves a constructor/call parameter
	 *
	 * @param \Tengyue\Infra\DiInterface dependencyInjector
	 * @param int position
	 * @param array argument
	 * @return mixed
	 */
	private function _buildParameter(DiInterface $dependencyInjector, $position, $argument)
	{
		/**
		 * All the arguments must have a type
		 */
		if (!isset($argument["type"])) {
			throw new Exception("Argument at position " . $position . " must have a type");
		}
        
        $type = $argument["type"];
		switch ($type) {

			/**
			 * If the argument type is 'service', we obtain the service from the DI
			 */
			case "service":
				if (!isset($argument["name"])) {
					throw new Exception("Service 'name' is required in parameter on position " . $position);
				}
				if (!is_object($dependencyInjector)) {
					throw new Exception("The dependency injector container is not valid");
				}
				return $dependencyInjector->get($argument["name"]);

			/**
			 * If the argument type is 'parameter', we assign the value as it is
			 */
			case "parameter":
				if (!isset($argument["value"])) {
					throw new Exception("Service 'value' is required in parameter on position " . $position);
				}
				return $argument["value"];

			/**
			 * If the argument type is 'instance', we assign the value as it is
			 */
			case "instance":

				if (!isset($argument["className"])) {
					throw new Exception("Service 'className' is required in parameter on position " . $position);
                }
                $name = $argument["className"];

				if (!is_object($dependencyInjector)) {
					throw new Exception("The dependency injector container is not valid");
				}

				if (isset($argument["arguments"])) {
                    $instanceArguments = $argument["arguments"];
					/**
					 * Build the instance with arguments
					 */
					return $dependencyInjector->get($name, $instanceArguments);
				}

				/**
				 * The instance parameter does not have arguments for its constructor
				 */
				return $dependencyInjector->get($name);

			default:
				/**
				 * Unknown parameter type
				 */
				throw new Exception("Unknown service type in parameter on position " . $position);
		}
	}

	/**
	 * Resolves an array of parameters
	 */
	private function _buildParameters(DiInterface $dependencyInjector, $arguments)
	{
		$buildArguments = [];
		foreach ($arguments as $position => $argument) {
			$buildArguments[] = $this->_buildParameter($dependencyInjector, $position, $argument);
		}
		return $buildArguments;
	}

	/**
	 * Builds a service using a complex service definition
	 *
	 * @param \Tengyue\Infra\DiInterface dependencyInjector
	 * @param array definition
	 * @param array parameters
	 * @return mixed
	 */
	public function build(DiInterface $dependencyInjector, $definition, $parameters = null)
	{
		/**
		 * The class name is required
		 */
		if (!isset($definitioxn["className"])) {
			throw new Exception("Invalid service definition. Missing 'className' parameter");
        }
        $className = $definition["className"];

		if (is_array($parameters == "array")) {

			/**
			 * Build the instance overriding the definition constructor parameters
			 */
			if (count($parameters)) {
                $instance = new $className(...$parameters);
            } else {
				$instance = new $className();
			}

		} else {

			/**
			 * Check if the argument has constructor arguments
			 */
			if (isset($definition["arguments"])) {

				/**
				 * Create the instance based on the parameters
				 */
                $instance = new $className(...$this->_buildParameters($dependencyInjector, $definition["arguments"]));
			} else {
				$instance = new $className();
			}
		}

		/**
		 * The definition has calls?
		 */
		if (isset($definition["calls"])) {
            
            $paramCalls = $definition["calls"];
			if (!is_object($instance)) {
				throw new Exception(
					"The definition has setter injection parameters but the constructor didn't return an instance"
				);
			}

			if (!is_array($paramCalls)) {
				throw new Exception("Setter injection parameters must be an array");
			}

			/**
			 * The method call has parameters
			 */
			foreach ($paramCalls as $methodPosition => $method) {

				/**
				 * The call parameter must be an array of arrays
				 */
				if (!is_array($method)) {
					throw new Exception("Method call must be an array on position " . $methodPosition);
				}

				/**
				 * A param 'method' is required
				 */
				if (!isset($method["method"])) {
					throw new Exception("The method name is required on position " . $methodPosition);
                }
                $methodName = $method["method"];

				/**
				 * Create the method call
				 */
				$methodCall = [$instance, $methodName];

				if (isset($method["arguments"])) {
                    $arguments = $method["arguments"];
					if (!is_array($arguments)) {
						throw new Exception("Call arguments must be an array " . $methodPosition);
					}

					if (count($arguments)) {

						/**
						 * Call the method on the instance
						 */
						call_user_func_array($methodCall, $this->_buildParameters($dependencyInjector, $arguments));

						/**
						 * Go to next method call
						 */
						continue;
					}
				}

				/**
				 * Call the method on the instance without arguments
				 */
				call_user_func($methodCall);
			}
		}

		/**
		 * The definition has properties?
		 */
		if (isset($definition["properties"])) {
            $paramCalls = $definition["properties"];
			if (!is_object($instance)) {
				throw new Exception(
					"The definition has properties injection parameters but the constructor didn't return an instance"
				);
			}

			if (!is_array($paramCalls)) {
				throw new Exception("Setter injection parameters must be an array");
			}

			/**
			 * The method call has parameters
			 */
			foreach ($paramCalls as $propertyPosition => $property) {

				/**
				 * The call parameter must be an array of arrays
				 */
				if (!is_array($property)) {
					throw new Exception("Property must be an array on position " . $propertyPosition);
				}

				/**
				 * A param 'name' is required
				 */
				if (!isset($property["name"])) {
					throw new Exception("The property name is required on position " . $propertyPosition);
                }
                $propertyName = $property["name"];

				/**
				 * A param 'value' is required
				 */
				if (!isset($property["value"])) {
					throw new Exception("The property value is required on position " . $propertyPosition);
                }
                $propertyValue = $property["value"];

				/**
				 * Update the public property
				 */
				$instance->{$propertyName} = $this->_buildParameter($dependencyInjector, $propertyPosition, $propertyValue);
			}
		}

		return $instance;
	}
}
