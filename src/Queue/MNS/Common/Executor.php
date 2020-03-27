<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 16/8/19
 * Time: 上午11:35
 */

namespace Tengyue\Infra\Queue\MNS\Common;

class Executor
{

	private $pool = null;

	public function __construct(IAsyncMessageRouter $pool)
    {
		$this->pool = $pool;
	}

	public function run()
    {
		$consumer = $this->pool->getConsumer();
		do {
			try {
				$consumer->receiveMessage($this->pool);
			} catch (\Exception $e) {
				// 任何业务异常都忽略
				echo "AsyncHandler Process Message Failed: " . $e->getMessage() . "\n";
				echo "ErrorCode: " . $e->getCode() . "\n";
			}
		} while (true);
	}

}