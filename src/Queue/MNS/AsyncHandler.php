<?php

namespace Tengyue\Infra\Queue\MNS;

use Tengyue\Infra\Di\Injectable;
use Tengyue\Infra\Queue\MNS\Common\IAsyncHandler;
use Tengyue\Infra\Queue\MNS\Common\ReceiveMessageResponse;

abstract class AsyncHandler extends Injectable implements IAsyncHandler
{
    /**
     * @param mixed $message
     * @return \Tengyue\Infra\Queue\MNS\Common\SendMessageResponse
     * @throws \Tengyue\Infra\Queue\Exception
     */
    public function call($message)
    {
        $router = AsyncMessageRouter::getInstance();
        return $router->sendMessage($this, $message);
    }

    /**
     * @param mixed $message
     * @param int $intervalMilliSecond
     * @return \Tengyue\Infra\Queue\MNS\Common\SendMessageResponse
     * @throws \Tengyue\Infra\Queue\Exception
     */
    public function callInterval($message, $intervalMilliSecond)
    {
        $router = AsyncMessageRouter::getInstance();
        return $router->sendDelayProcessingMessage(
            $this, $message, $intervalMilliSecond
        );
    }

    abstract public function process(ReceiveMessageResponse $res);
}