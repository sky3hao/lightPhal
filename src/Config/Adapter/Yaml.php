<?php

namespace Tengyue\Infra\Config\Adapter;

use Tengyue\Infra\Config;
use Tengyue\Infra\Config\Exception;

/**
 * Tengyue\Infra\Config\Adapter\Yaml
 *
 * Reads YAML files and converts them to Tengyue\Infra\Config objects.
 *
 * Given the following configuration file:
 *
 *<code>
 * Tengyue\Infra:
 *   baseuri:        /Tengyue\Infra/
 *   controllersDir: !approot  /app/controllers/
 * models:
 *   metadata: memory
 *</code>
 *
 * You can read it as follows:
 *
 *<code>
 * define(
 *     "APPROOT",
 *     dirname(__DIR__)
 * );
 *
 * $config = new \Tengyue\Infra\Config\Adapter\Yaml(
 *     "path/config.yaml",
 *     [
 *         "!approot" => function($value) {
 *             return APPROOT . $value;
 *         },
 *     ]
 * );
 *
 * echo $config->Tengyue\Infra->controllersDir;
 * echo $config->Tengyue\Infra->baseuri;
 * echo $config->models->metadata;
 *</code>
 */
class Yaml extends Config
{

	/**
	 * Tengyue\Infra\Config\Adapter\Yaml constructor
	 *
	 * @throws \Tengyue\Infra\Config\Exception
	 */
	public function __construct($filePath, $callbacks = null)
	{
		$ndocs = 0;

		if (!extension_loaded("yaml")) {
			throw new Exception("Yaml extension not loaded");
		}

		if ($callbacks !== null) {
			$yamlConfig = yaml_parse_file($filePath, 0, $ndocs, $callbacks);
		} else {
			$yamlConfig = yaml_parse_file($filePath);
		}

		if ($yamlConfig === false) {
			throw new Exception("Configuration file " . basename($filePath) . " can't be loaded");
		}

		parent::__construct($yamlConfig);
	}
}
