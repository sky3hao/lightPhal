<?php

namespace Tengyue\Infra\Cache\Frontend;

use Tengyue\Infra\Cache\FrontendInterface;

/**
 * Tengyue\Infra\Cache\Frontend\Data
 *
 * Allows to cache native PHP data in a serialized form
 *
 *<code>
 * use Tengyue\Infra\Cache\Backend\File;
 * use Tengyue\Infra\Cache\Frontend\Data;
 *
 * // Cache the files for 2 days using a Data frontend
 * $frontCache = new Data(
 *     [
 *         "lifetime" => 172800,
 *     ]
 * );
 *
 * // Try to get cached records
 * $robots = $cache->get($cacheKey);
 *
 * if ($robots === null) {
 *     // $robots is null due to cache expiration or data does not exist
 *     // Make the database call and populate the variable
 *     $robots = Robots::find(
 *         [
 *             "order" => "id",
 *         ]
 *     );
 *
 *     // Store it in the cache
 *     $cache->save($cacheKey, $robots);
 * }
 *
 * // Use $robots :)
 * foreach ($robots as $robot) {
 *     echo $robot->name, "\n";
 * }
 *</code>
 */
class Data implements FrontendInterface
{

	protected $_frontendOptions;

	/**
	 * Tengyue\Infra\Cache\Frontend\Data constructor
	 *
	 * @param array frontendOptions
	 */
	public function __construct($frontendOptions = null)
	{
		$this->_frontendOptions = $frontendOptions;
	}

	/**
	 * Returns the cache lifetime
	 */
	public function getLifetime()
	{
		$options = $this->_frontendOptions;
		if (is_array($options)) {
			if (isset($options["lifetime"])) {
				$lifetime = $options["lifetime"];
				return $lifetime;
			}
		}
		return 1;
	}

	/**
	 * Check whether if frontend is buffering output
	 */
	public function isBuffering()
	{
		return false;
	}

	/**
	 * Starts output frontend. Actually, does nothing
	 */
	public function start()
	{

	}

	/**
	 * Returns output cached content
	 *
	 * @return string
	 */
	public function getContent()
	{
		return null;
	}

	/**
	 * Stops output frontend
	 */
	public function stop()
	{

	}

	/**
	 * Serializes data before storing them
	 */
	public function beforeStore($data)
	{
		return serialize($data);
	}

	/**
	 * Unserializes data after retrieval
	 */
	public function afterRetrieve($data)
	{
		if (is_numeric($data)) {
			return $data;
		}

		// do not unserialize empty string, null, false, etc
		if (empty($data)) {
			return $data;
		}

		return unserialize($data);
	}
}
