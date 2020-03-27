<?php

namespace Tengyue\Infra\Http\Response;

use Tengyue\Infra\Helper\Common;
use Tengyue\Infra\Http\Response\HeadersInterface;

/**
 * Tengyue\Infra\Http\Response\Headers
 *
 * This class is a bag to manage the response headers
 */
class Headers implements HeadersInterface
{
	protected $_headers = [];

	/**
	 * Sets a header to be sent at the end of the request
	 */
	public function set($name, $value)
	{
		$this->_headers[$name] = $value;
	}

	/**
	 * Gets a header value from the internal bag
	 */
	public function get($name)
	{
		$headers =$this->_headers;

		if (isset($headers[$name])) {
			return $headers[$name];
		}

		return false;
	}

	/**
	 * Sets a raw header to be sent at the end of the request
	 */
	public function setRaw($header)
	{
		$this->_headers[$header] = null;
	}

	/**
	 * Removes a header to be sent at the end of the request
	 */
	public function remove($header)
	{
		$headers = $this->_headers;
		unset($headers[$header]);
		$this->_headers = $headers;
	}

	/**
	 * Sends the headers to the client
	 */
	public function send()
	{
		if (!headers_sent()) {
			foreach ($this->_headers as $header => $value) {
				if ($value !== null) {
					header($header . ": " . $value, true);
				} else {
					if (Common::memstr($header, ":") || substr($header, 0, 5) == "HTTP/") {
						header($header, true);
					} else {
						header($header . ": ", true);
					}
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Reset set headers
	 */
	public function reset()
	{
		$this->_headers = [];
	}

	/**
	 * Returns the current headers as an array
	 */
	public function toArray()
	{
		return $this->_headers;
	}

	/**
	 * Restore a \Tengyue\Infra\Http\Response\Headers object
	 */
	public static function __set_state($data)
	{
		$headers = new self();
		if (isset($data["_headers"])) {
			foreach ($data["_headers"] as $key => $value) {
				$headers->set($key, $value);
			}
		}
		return $headers;
	}
}
