<?php

namespace Tengyue\Infra\Di;

use Tengyue\Infra\DiInterface;

/**
 * Tengyue\Infra\Di\InjectionAwareInterface
 *
 * This interface must be implemented in those classes that uses internally the Tengyue\Infra\Di that creates them
 */
interface InjectionAwareInterface
{

	/**
	 * Sets the dependency injector
	 */
	public function setDI(DiInterface $dependencyInjector);

	/**
	 * Returns the internal dependency injector
	 */
	public function getDI();
}
