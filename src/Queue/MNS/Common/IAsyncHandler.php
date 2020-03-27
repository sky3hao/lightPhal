<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 16/8/19
 * Time: 上午11:42
 */

namespace Tengyue\Infra\Queue\MNS\Common;


interface IAsyncHandler
{

	/**
	 * @param mixed $message
	 * @return SendMessageResponse
	 */
	public function call($message);

	/**
	 * @param mixed $message
	 * @param int $intervalMilliSecond
	 * @return SendMessageResponse
	 */
	public function callInterval($message, $intervalMilliSecond);

	/**
	 * @param ReceiveMessageResponse $res
	 * @return boolean
	 */
	public function process(ReceiveMessageResponse $res);

}