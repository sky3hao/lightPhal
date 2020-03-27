<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 16/8/19
 * Time: 上午11:42
 */

namespace Tengyue\Infra\Queue\MNS\Common;

use Tengyue\Infra\Queue\MNS\Exception\HandlerNotFound;

interface IAsyncMessageRouter
{

	/**
	 * @param IAsyncHandler $handler
	 * @return boolean
	 */
	public function registerHandler(IAsyncHandler $handler);

	/**
	 * @param string $className
	 * @return IAsyncHandler
	 */
	public function getHandler($className);

	/**
	 * @return \Tengyue\Infra\Queue\MNS\Common\IConsumer
	 */
	public function getConsumer();

	/**
	 * @param IAsyncHandler $handler
	 * @param mixed $message
	 * @return SendMessageResponse
	 */
	public function sendMessage(IAsyncHandler $handler, $message);

	/**
	 * @param IAsyncHandler $handler
	 * @param mixed $message
	 * @param int $intervalMilliSecond
	 * @return SendMessageResponse
	 */
	public function sendDelayProcessingMessage(IAsyncHandler $handler, $message, $intervalMilliSecond);

	/**
	 * @param ReceiveMessageResponse $res
	 * @return IAsyncHandler
	 * @throws HandlerNotFound if no IAsyncHandler found
	 */
	public function findHandler(ReceiveMessageResponse $res);

	/**
	 * @param IAsyncHandler $handler
	 * @param ReceiveMessageResponse $res
	 * @return boolean
	 */
	public function processMessage(IAsyncHandler $handler, ReceiveMessageResponse $res);

}