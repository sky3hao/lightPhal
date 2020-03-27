<?php

namespace Tengyue\Infra\Config\Adapter;

use Tengyue\Infra\Config;

/**
 * Tengyue\Infra\Config\Adapter\Json
 *
 * Reads JSON files and converts them to Tengyue\Infra\Config objects.
 *
 * Given the following configuration file:
 *
 *<code>
 * {"Tengyue\Infra":{"baseuri":"\/Tengyue\Infra\/"},"models":{"metadata":"memory"}}
 *</code>
 *
 * You can read it as follows:
 *
 *<code>
 * $config = new Tengyue\Infra\Config\Adapter\Json("path/config.json");
 *
 * echo $config->Tengyue\Infra->baseuri;
 * echo $config->models->metadata;
 *</code>
 */
class Json extends Config
{

	/**
	 * Tengyue\Infra\Config\Adapter\Json constructor
	 */
	public function __construct($filePath)
	{
		parent::__construct(json_decode(file_get_contents($filePath), true));
	}
}
