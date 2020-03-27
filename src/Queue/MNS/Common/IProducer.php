<?php

namespace Tengyue\Infra\Queue\MNS\Common;

interface IProducer
{

	/**
	 * @param string $message
	 * @return SendMessageResponse
	 */
	public function sendMessage($message);

	/**
	 * @param string $message
	 * @param int $intervalMilliSecond
	 * @return SendMessageResponse
	 */
	public function sendDelayProcessingMessage($message, $intervalMilliSecond);

}