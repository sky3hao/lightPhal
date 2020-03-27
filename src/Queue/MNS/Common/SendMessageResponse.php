<?php

namespace Tengyue\Infra\Queue\MNS\Common;

class SendMessageResponse
{
	private $messageId;
	private $messageBodyMD5;
	private $isSucceed;
	private $statusCode;

	/**
	 * @return mixed
	 */
	public function getMessageId()
	{
		return $this->messageId;
	}

	/**
	 * @param mixed $messageId
	 */
	public function setMessageId($messageId)
	{
		$this->messageId = $messageId;
	}

	/**
	 * @return mixed
	 */
	public function getMessageBodyMD5()
	{
		return $this->messageBodyMD5;
	}

	/**
	 * @param mixed $messageBodyMD5
	 */
	public function setMessageBodyMD5($messageBodyMD5)
	{
		$this->messageBodyMD5 = $messageBodyMD5;
	}

	/**
	 * @return mixed
	 */
	public function getIsSucceed()
	{
		return $this->isSucceed;
	}

	/**
	 * @param mixed $isSucceed
	 */
	public function setIsSucceed($isSucceed)
	{
		$this->isSucceed = $isSucceed;
	}

	/**
	 * @return mixed
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * @param mixed $statusCode
	 */
	public function setStatusCode($statusCode)
	{
		$this->statusCode = $statusCode;
	}
    
}