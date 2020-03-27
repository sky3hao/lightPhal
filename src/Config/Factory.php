<?php

namespace Tengyue\Infra\Config;

use Tengyue\Infra\Factory as BaseFactory;
use Tengyue\Infra\Config;
use Tengyue\Infra\Helper\Common;

/**
 * Loads Config Adapter class using 'adapter' option, if no extension is provided it will be added to filePath
 *
 *<code>
 * use Tengyue\Infra\Config\Factory;
 *
 * $options = [
 *     "filePath" => "path/config",
 *     "adapter"  => "php",
 * ];
 * $config = Factory::load($options);
 *</code>
 */
class Factory extends BaseFactory
{
    public static function load($config)
    {
        return self::loadClass("Tengyue\\Infra\\Config\\Adapter", $config);
    }

    protected static function loadClass($namespace, $config)
    {
        if (is_string($config)) {
            $oldConfig = $config;
            $extension = substr(strrchr($config, "."), 1);

            if (empty($extension)) {
                throw new Exception("You need to provide extension in file path");
            }

            $config = [
                "adapter" => $extension,
                "filePath" => $oldConfig
            ];
        }

        if (is_object($config) && $config instanceof Config) {
            $config = $config->toArray();
        }

        if (!is_array($config)) {
            throw new Exception("Config must be array or Tengyue\\Infra\\Config object");
        }

        if (!isset($config["filePath"])) {
            throw new Exception("You must provide 'filePath' option in factory config parameter.");
        }

        $filePath = $config["filePath"];
        if (isset($config["adapter"])) {
            $adapter = $config["adapter"];
            $className = $namespace."\\". Common::camelize($adapter);
            if (!strpos($filePath, ".")) {
                $filePath = $filePath.".".lcfirst($adapter);
            }

            if ($className == "Tengyue\\Infra\\Config\\Adapter\\Ini") {
                if (isset($config["mode"])) {
                    return new $className($filePath, $config["mode"]);
                }
            } elseif ($className == "Tengyue\\Infra\\Config\\Adapter\\Yaml") {
                if (isset($config["callbacks"])) {
                    return new $className($filePath, $config["callbacks"]);
                }
            }

            return new $className($filePath);
        }

        throw new Exception("You must provide 'adapter' option in factory config parameter.");
    }
}
