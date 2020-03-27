<?php

namespace Tengyue\Infra;

use Tengyue\Infra\Factory\Exception;
use Tengyue\Infra\Config;

abstract class Factory implements FactoryInterface
{
	protected static function loadClass($namespace, $config)
	{
		if (is_object($config) && ($config instanceof Config)) {
			$config = $config->toArray();
		}

		if (!is_array($config)) {
			throw new Exception("Config must be array or Tengyue\Infra\Config object");
		}

		if (isset($config["adapter"])) {
            $adapter = $config["adapter"];
			unset($config["adapter"]);
			$className = $namespace."\\".$adapter;

			return new $className($config);
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}
}
