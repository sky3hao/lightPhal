<?php

namespace Tengyue\Infra\Http;

use Tengyue\Infra\Http\Response\HeadersInterface;

/**
 * Tengyue\Infra\Http\Response
 *
 * Interface for Tengyue\Infra\Http\Response
 */
interface ResponseInterface
{

	/**
	 * Sets the HTTP response code
	 */
	public function setStatusCode($code, $message = null);

	/**
	 * Returns headers set by the user
	 */
	public function getHeaders();

	/**
	 * Overwrites a header in the response
	 */
	public function setHeader($name, $value);

	/**
	 * Send a raw header to the response
	 */
	public function setRawHeader($header);

	/**
	 * Resets all the established headers
	 */
	public function resetHeaders();

	/**
	 * Sets output expire time header
	 */
	public function setExpires(\DateTime $datetime);

	/**
	 * Sends a Not-Modified response
	 */
	public function setNotModified();

	/**
	 * Sets the response content-type mime, optionally the charset
	 *
	 * @param $contentType
	 * @param $charset
	 * @return \Tengyue\Infra\Http\ResponseInterface
	 */
	public function setContentType($contentType, $charset = null);

	/**
	 * Sets the response content-length
	 */
	public function setContentLength($contentLength);

	/**
	 * Redirect by HTTP to another action or URL
	 */
	public function redirect($location = null, $externalRedirect = false, $statusCode = 302);

	/**
	 * Sets HTTP response body
	 */
	public function setContent($content);

	/**
	 * Sets HTTP response body. The parameter is automatically converted to JSON
	 *
	 *<code>
	 * $response->setJsonContent(
	 *     [
	 *         "status" => "OK",
	 *     ]
	 * );
	 *</code>
	 */
	public function setJsonContent($content);

	/**
	 * Appends a $to the HTTP response body
	 */
	public function appendContent($content);

	/**
	 * Gets the HTTP response body
	 */
	public function getContent();

	/**
	 * Sends headers to the client
	 */
	public function sendHeaders();

	/**
	 * Prints out HTTP response to the client
	 */
	public function send();

	/**
	 * Sets an attached file to be sent at the end of the request
	 */
	public function setFileToSend($filePath, $attachmentName = null);

}
