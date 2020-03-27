<?php

namespace Tengyue\Infra\Http\Response;

/**
 * Tengyue\Infra\Http\Response\HeadersInterface
 *
 * Interface for Tengyue\Infra\Http\Response\Headers compatible bags
 */
interface HeadersInterface
{

	/**
	 * Sets a header to be sent at the end of the request
	 */
	public function set($name, $value);

	/**
	 * Gets a header value from the internal bag
	 */
	public function get($name);

	/**
	 * Sets a raw header to be sent at the end of the request
	 */
	public function setRaw($header);

	/**
	 * Sends the headers to the client
	 */
	public function send();

	/**
	 * Reset set headers
	 */
	public function reset();

	/**
	 * Restore a \Tengyue\Infra\Http\Response\Headers object
	 */
	public static function __set_state($data);

}
