<?php

namespace Tengyue\Infra\Config\Adapter;

use Tengyue\Infra\Config;
use Tengyue\Infra\Factory\Exception;
use Tengyue\Infra\Config\Factory;

/**
 * Tengyue\Infra\Config\Adapter\Grouped
 *
 * Reads multiple files (or arrays) and merges them all together.
 *
 * <code>
 * use Tengyue\Infra\Config\Adapter\Grouped;
 *
 * $config = new Grouped(
 *     [
 *         "path/to/config.php",
 *         "path/to/config.dist.php",
 *     ]
 * );
 * </code>
 *
 * <code>
 * use Tengyue\Infra\Config\Adapter\Grouped;
 *
 * $config = new Grouped(
 *     [
 *         "path/to/config.json",
 *         "path/to/config.dist.json",
 *     ],
 *     "json"
 * );
 * </code>
 *
 * <code>
 * use Tengyue\Infra\Config\Adapter\Grouped;
 *
 * $config = new Grouped(
 *     [
 *         [
 *             "filePath" => "path/to/config.php",
 *             "adapter"  => "php",
 *         ],
 *         [
 *             "filePath" => "path/to/config.json",
 *             "adapter"  => "json",
 *         ],
 *         [
 *             "adapter"  => "array",
 *             "config"   => [
 *                 "property" => "value",
 *         ],
 *     ],
 * );
 * </code>
 */
class Grouped extends Config
{

	/**
	 * Tengyue\Infra\Config\Adapter\Grouped constructor
	 */
	public function __construct($arrayConfig, $defaultAdapter = "php")
	{
		parent::__construct([]);

		foreach ($arrayConfig as $configName) {
			$configInstance = $configName;

			// Set to default adapter if passed as string
			if (is_string($configName)) {
				$configInstance = ["filePath" => $configName, "adapter" => $defaultAdapter];
			} elseif (!isset($configInstance["adapter"])) {
				$configInstance["adapter"] = $defaultAdapter;
			}

			if ($configInstance["adapter"] === "array") {
				if (!isset($configInstance["config"])) {
					throw new Exception("To use 'array' adapter you have to specify the 'config' as an array.");
				} else {
					$configArray    = $configInstance["config"];
					$configInstance = new Config($configArray);
				}
			} else {
				$configInstance = Factory::load($configInstance);
			}

			$this->_merge($configInstance);
		}
	}
}
