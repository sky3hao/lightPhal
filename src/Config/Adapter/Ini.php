<?php

namespace Tengyue\Infra\Config\Adapter;

use Tengyue\Infra\Config;
use Tengyue\Infra\Config\Exception;

/**
 * Tengyue\Infra\Config\Adapter\Ini
 *
 * Reads ini files and converts them to Tengyue\Infra\Config objects.
 *
 * Given the next configuration file:
 *
 *<code>
 * [database]
 * adapter = Mysql
 * host = localhost
 * username = scott
 * password = cheetah
 * dbname = test_db
 *
 * [app]
 * controllersDir = "../app/controllers/"
 * modelsDir = "../app/models/"
 * </code>
 *
 * You can read it as follows:
 *
 *<code>
 * $config = new \Tengyue\Infra\Config\Adapter\Ini("path/config.ini");
 *
 * echo $config->Tengyue\Infra->controllersDir;
 * echo $config->database->username;
 *</code>
 *
 * PHP constants may also be parsed in the ini file, so if you define a constant
 * as an ini value before calling the constructor, the constant's value will be
 * integrated into the results. To use it this way you must specify the optional
 * second parameter as INI_SCANNER_NORMAL when calling the constructor:
 *
 * <code>
 * $config = new \Tengyue\Infra\Config\Adapter\Ini(
 *     "path/config-with-constants.ini",
 *     INI_SCANNER_NORMAL
 * );
 * </code>
 */
class Ini extends Config
{

	/**
	 * Tengyue\Infra\Config\Adapter\Ini constructor
	 */
	public function __construct($filePath, $mode = null)
	{
		// Default to INI_SCANNER_RAW if not specified
		if (null === $mode) {
			$mode = INI_SCANNER_RAW;
		}

		$iniConfig = parse_ini_file($filePath, true, $mode);
		if ($iniConfig === false) {
			throw new Exception("Configuration file " . basename($filePath) . " can't be loaded");
		}

		$config = [];

		foreach ($iniConfig as $section => $directives) {
			if (is_array($directives)) {
				$sections = [];
				foreach ($directives as $path => $lastValue) {
					$sections[] = $this->_parseIniString((string)$path, $lastValue);
				}
				if (count($sections)) {
					$config[$section] = call_user_func_array("array_merge_recursive", $sections);
				}
			} else {
				$config[$section] = $this->_cast($directives);
			}
		}

		parent::__construct($config);
	}

	/**
	 * Build multidimensional array from string
	 *
	 * <code>
	 * $this->_parseIniString("path.hello.world", "value for last key");
	 *
	 * // result
	 * [
	 *      "path" => [
	 *          "hello" => [
	 *              "world" => "value for last key",
	 *          ],
	 *      ],
	 * ];
	 * </code>
	 */
	protected function _parseIniString($path, $value)
	{
		$value = $this->_cast($value);
		$pos = strpos($path, ".");

		if ($pos === false) {
			return [$path => $value];
		}

		$key = substr($path, 0, $pos);
		$path = substr($path, $pos + 1);

		return [$key => $this->_parseIniString($path, $value)];
	}
	/**
	 * We have to cast values manually because parse_ini_file() has a poor implementation.
	 *
	 * @param mixed ini The array casted by `parse_ini_file`
	 */
	protected function _cast($ini)
	{
		if (is_array($ini)) {
			foreach ($ini as $key => $val) {
				$ini[$key] = $this->_cast($val);
			}
		}
		if (is_string($ini)) {
			// Decode true
			if ($ini === "true" || $ini === "yes" || strtolower($ini) === "on") {
				return true;
			}

			// Decode false
			if ($ini === "false" || $ini === "no" || strtolower($ini) === "off") {
				return false;
			}

			// Decode null
			if ($ini === "null") {
				return null;
			}

			// Decode float/int
			if (is_numeric($ini)) {
				if (preg_match("/[.]+/", $ini)) {
					return (double)$ini;
				} else {
					return (int)$ini;
				}
			}
		}
		return $ini;
	}
}
