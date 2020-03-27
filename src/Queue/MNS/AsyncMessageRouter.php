<?php

namespace Tengyue\Infra\Queue\MNS;

use Tengyue\Infra\Queue\MNS\Common\AbstractAsyncMessageRouter;
use Tengyue\Infra\Queue\MNS\Common\IAsyncHandler;

class AsyncMessageRouter extends AbstractAsyncMessageRouter
{

    private static $instance = null;

	/**
	 * @param IAsyncHandler $handler
	 * @return void
	 */
	public function registerHandler(IAsyncHandler $handler)
	{
		return parent::registerHandler($handler);
	}

    /**
     * @return null|AsyncMessageRouter
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}