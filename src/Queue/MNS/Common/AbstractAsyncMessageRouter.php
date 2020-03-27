<?php

namespace Tengyue\Infra\Queue\MNS\Common;

use Tengyue\Infra\Di\Injectable;
use Tengyue\Infra\Queue\Exception;
use Tengyue\Infra\Queue\MNS\Exception\HandlerNotFound;

abstract class AbstractAsyncMessageRouter extends Injectable implements IAsyncMessageRouter
{
    private $__pool = [];

	/**
	 * @param IAsyncHandler $handler
	 * @return void
	 */
	public function registerHandler(IAsyncHandler $handler)
	{
		$className = get_class($handler);
		$this->__pool[$className] = $handler;
	}

	/**
	 * @param string $className
	 * @return IAsyncHandler
	 */
	public function getHandler($className)
    {
		return $this->__pool[$className];
	}

    /**
     * 寻找消息对应的 Handler,这个逻辑由具体的 MessageRouter 指定
     * @param ReceiveMessageResponse $res
     * @return IAsyncHandler
     * @throws HandlerNotFound if no IAsyncHandler found
     */
    public function findHandler(ReceiveMessageResponse $res)
    {
        $handlerName = (new Message())
            ->parseBind($res->getMessageBody())
            ->getHandlerName();

        $handler = $this->getHandler($handlerName);

        if ($handler && is_a($handler, IAsyncHandler::class)) {
            return $handler;
        }

        throw new HandlerNotFound($handlerName);
    }

    /**
     * 驱动 Handler 处理 ReceiveMessageResponse
     * @param IAsyncHandler $handler
     * @param ReceiveMessageResponse $res
     * @return boolean
     */
    public function processMessage(IAsyncHandler $handler, ReceiveMessageResponse $res)
    {
        $messageBody = (new Message())
            ->parseBind($res->getMessageBody())
            ->getMessageBody();

        $res->setMessageBody($messageBody);

        return $handler->process($res);
    }

    /**
     * @return IConsumer|\Tengyue\Infra\Queue\MNS\MNS\Consumer
     * @throws Exception
     */
    public function getConsumer()
    {
        if (!$this->getDI()->has("mnsConsumer")) {
            throw new Exception("The DI service mnsConsumer not exsits");
        }
        $consumer = $this->mnsConsumer;

        return $consumer;
    }

    /**
     * @param IAsyncHandler $handler
     * @param mixed $message
     * @return SendMessageResponse
     * @throws Exception
     */
    public function sendMessage(IAsyncHandler $handler, $message)
    {
        $handlerName = get_class($handler);

        if (!$this->getDI()->has("mnsProducer")) {
            throw new Exception("The DI service mnsProducer not exsits");
        }
        $producer = $this->mnsProducer;

        return $producer->sendMessage(
            (new Message())
                ->setHandlerName($handlerName)
                ->setMessageBody($message)
                ->getSerializeBody()
        );
    }

    /**
     * @param IAsyncHandler $handler
     * @param mixed $message
     * @param int $intervalMilliSecond
     * @return SendMessageResponse
     * @throws Exception
     */
    public function sendDelayProcessingMessage(IAsyncHandler $handler, $message, $intervalMilliSecond)
    {
        $handlerName = get_class($handler);

        if (!$this->getDI()->has("mnsProducer")) {
            throw new Exception("The DI service mnsProducer not exsits");
        }
        $producer = $this->mnsProducer;

        return $producer->sendDelayProcessingMessage(
            (new Message())
                ->setHandlerName($handlerName)
                ->setMessageBody($message)
                ->getSerializeBody(),
            $intervalMilliSecond
        );
    }

}