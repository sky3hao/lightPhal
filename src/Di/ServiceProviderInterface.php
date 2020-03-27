<?php

namespace Tengyue\Infra\Di;

use Tengyue\Infra\DiInterface;

/**
 * Tengyue\Infra\Di\ServiceProviderInterface
 *
 * Should be implemented by service providers, or such components,
 * which register a service in the service container.
 *
 * <code>
 * namespace Acme;
 *
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
interface ServiceProviderInterface
{
	/**
	 * Registers a service provider.
	 */
	public function register(DiInterface $di);
}
