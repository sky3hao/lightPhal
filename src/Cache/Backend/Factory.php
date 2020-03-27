<?php

namespace Tengyue\Infra\Cache\Backend;

use Tengyue\Infra\Factory as BaseFactory;
use Tengyue\Infra\Factory\Exception;
use Tengyue\Infra\Cache\BackendInterface;
use Tengyue\Infra\Cache\Frontend\Factory as FrontendFactory;
use Tengyue\Infra\Config;
use Tengyue\Infra\Helper\Common;

/**
 *
 *<code>
 * use Tengyue\Infra\Cache\Backend\Factory;
 * use Tengyue\Infra\Cache\Frontend\Data;
 *
 * $options = [
 *     "prefix"   => "app-data",
 *     "frontend" => new Data(),
 *     "adapter"  => "apc",
 * ];
 * $backendCache = Factory::load($options);
 *</code>
 */
class Factory extends BaseFactory
{
    /**
     * @param $config
     * @return mixed
     * @throws Exception
     */
	public static function load($config)
	{
		return self::loadClass("Tengyue\\Infra\\Cache\\Backend", $config);
	}

    /**
     * @param $namespace
     * @param $config
     * @return mixed
     * @throws Exception
     */
	protected static function loadClass($namespace, $config)
	{
		if (is_object($config) && $config instanceof Config) {
			$config = $config->toArray();
		}

		if (!is_array($config)) {
			throw new Exception("Config must be array or Tengyue\Infra\Config object");
		}

		if (!isset($config["frontend"])) {
			throw new Exception("You must provide 'frontend' option in factory config parameter.");
		}
		$frontend = $config["frontend"];

		if (isset($config["adapter"])) {
			$adapter = $config["adapter"];
			unset($config["adapter"]);
			unset($config["frontend"]);
			if (is_array($frontend) || $frontend instanceof Config) {
				$frontend = FrontendFactory::load($frontend);
			}
			$className = $namespace."\\".Common::camelize($adapter);

			return new $className($frontend, $config);
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}
}
