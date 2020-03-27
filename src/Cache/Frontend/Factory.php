<?php

namespace Tengyue\Infra\Cache\Frontend;

use Tengyue\Infra\Cache\FrontendInterface;
use Tengyue\Infra\Factory\Exception;
use Tengyue\Infra\Factory as BaseFactory;
use Tengyue\Infra\Config;
use Tengyue\Infra\Helper\Common;

/**
 * Loads Frontend Cache Adapter class using 'adapter' option
 *
 *<code>
 * use Tengyue\Infra\Cache\Frontend\Factory;
 *
 * $options = [
 *     "lifetime" => 172800,
 *     "adapter"  => "data",
 * ];
 * $frontendCache = Factory::load($options);
 *</code>
 */
class Factory extends BaseFactory
{
	/**
	 * @param \Tengyue\Infra\Config|array config
	 */
	public static function load($config)
	{
		return self::loadClass("Tengyue\\Infra\\Cache\\Frontend", config);
	}

	protected static function loadClass($namespace, $config)
	{
		if (is_object($config) && $config instanceof Config) {
			$config = $config->toArray();
		}

		if (!is_array($config)) {
			throw new Exception("Config must be array or Tengyue\Infra\Config object");
		}

		if (isset($config["adapter"])) {
			$adapter = $config["adapter"];
			unset($config["adapter"]);
			$className = $namespace."\\".Common::camelize($adapter);

			if ($className == "Tengyue\\Infra\\Cache\\Frontend\\None") {
				return new $className();
			} else {
				return new $className($config);
			}
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}
}
