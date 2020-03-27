<?php

namespace Tengyue\Infra\Queue\MNS\Common;

interface IConsumer
{

	/**
	 * @param IAsyncMessageRouter $messageRouter
	 * @return boolean
	 */
	public function receiveMessage(IAsyncMessageRouter $messageRouter );

}