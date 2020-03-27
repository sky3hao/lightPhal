<?php

namespace Tengyue\Infra\Image;

use Tengyue\Infra\Factory as BaseFactory;
use Tengyue\Infra\Factory\Exception;
use Tengyue\Infra\Config;
use Tengyue\Infra\Helper\Common;
use Tengyue\Infra\Image;

/**
 * Loads Image Adapter class using 'adapter' option
 *
 *<code>
 * use Tengyue\Infra\Image\Factory;
 *
 * $options = [
 *     "width"   => 200,
 *     "height"  => 200,
 *     "file"    => "upload/test.jpg",
 *     "adapter" => "imagick",
 * ];
 * $image = Factory::load($options);
 *</code>
 */
class Factory extends BaseFactory
{
    /**
     *
     * @param \Tengyue\Infra\Config|array $config
     * @return Image | Adapter
     * @throws Exception
     */
	public static function load($config)
	{
		return self::loadClass("Tengyue\\Infra\\Image\\Adapter", $config);
	}

	protected static function loadClass($namespace, $config)
	{
		if (is_object($config) && $config instanceof Config) {
			$config = $config->toArray();
		}

		if (!is_array($config)) {
			throw new Exception("Config must be array or Tengyue\\Infra\\Config object");
		}

		if (!isset($config["file"])) {
			throw new Exception("You must provide 'file' option in factory config parameter.");
		}
		$file = $config["file"];

		if (!isset($config["adapter"])) {
		    $config['adapter'] = 'imagick';
        }

        $className = $namespace."\\".Common::camelize($config["adapter"]);

        if (isset($config["width"])) {
            $width = $config["width"];
            if (isset($config["height"])) {
                return new $className($file, $width, $config["height"]);
            }

            return new $className($file, $width);
        }

        return new $className($file);

	}
}
