<?php

namespace Tengyue\Infra\Config\Adapter;

use Tengyue\Infra\Config;

/**
 * Tengyue\Infra\Config\Adapter\Php
 *
 * Reads php files and converts them to Tengyue\Infra\Config objects.
 *
 * Given the next configuration file:
 *
 *<code>
 *<?php
 *
 * return [
 *     "database" => [
 *         "adapter"  => "Mysql",
 *         "host"     => "localhost",
 *         "username" => "scott",
 *         "password" => "cheetah",
 *         "dbname"   => "test_db",
 *     ],
 *     "app" => [
 *         "controllersDir" => "../app/controllers/",
 *         "modelsDir"      => "../app/models/",
 *     ],
 * ];
 *</code>
 *
 * You can read it as follows:
 *
 *<code>
 * $config = new \Tengyue\Infra\Config\Adapter\Php("path/config.php");
 *
 * echo $config->Tengyue\Infra->controllersDir;
 * echo $config->database->username;
 *</code>
 */
class Php extends Config
{

	/**
	 * Tengyue\Infra\Config\Adapter\Php constructor
	 */
	public function __construct($filePath)
	{
		parent::__construct(require $filePath);
	}
}
