<?php

namespace Tengyue\Infra\Queue\MNS\MNS;

use AliyunMNS\Client;
use AliyunMNS\Exception\MnsException;
use Tengyue\Infra\Di\Injectable;
use Tengyue\Infra\Helper\Common;
use Tengyue\Infra\Queue\MNS\Common\IAsyncMessageRouter;
use Tengyue\Infra\Queue\MNS\Common\IConsumer;
use Tengyue\Infra\Queue\MNS\Common\ReceiveMessageResponse;
use Tengyue\Infra\Queue\MNS\Exception\HandlerNotFound;

class Consumer extends Injectable implements IConsumer
{
    private $client;
    private $queue;

    public function __construct($endPoint, $accessId, $accessKey, $queueName)
    {
        $this->client = new Client($endPoint, $accessId, $accessKey);
        $this->queue = $this->client->getQueueRef($queueName);
    }

    /**
     * @param IAsyncMessageRouter $messageRouter
     * @return bool|void
     * @throws \Exception
     */
    public function receiveMessage(IAsyncMessageRouter $messageRouter)
    {
        $receiptHandle = NULL;
        try {
            // 1. 直接调用receiveMessage函数
            // 1.1 receiveMessage函数接受waitSeconds参数，无特殊情况这里都是建议设置为30
            // 1.2 waitSeconds非0表示这次receiveMessage是一次http long polling，如果queue内刚好没有message，那么这次request会在server端等到queue内有消息才返回。最长等待时间为waitSeconds的值，最大为30。
            $res = $this->queue->receiveMessage(30);

            $messageId = $res->getMessageId();
            $messageBodyMd5 = $res->getMessageBodyMD5();
            $this->logger->debug("$messageId $messageBodyMd5 ReceiveMessage Succeed!");

            // 2. 获取ReceiptHandle，这是一个有时效性的Handle，可以用来设置Message的各种属性和删除Message。具体的解释请参考：help.aliyun.com/document_detail/27477.html 页面里的ReceiptHandle
            $receiptHandle = $res->getReceiptHandle();

            // 初始化 ReceiveMessageResponse
            $receiveMessageResponse = new ReceiveMessageResponse();
            $receiveMessageResponse->setIsSucceed($res->isSucceed());
            $receiveMessageResponse->setStatusCode($res->getStatusCode());
            $receiveMessageResponse->setMessageId($messageId);
            $receiveMessageResponse->setMessageBody($res->getMessageBody());
            $receiveMessageResponse->setMessageBodyMD5($messageBodyMd5);

            try {
                // 寻找 Message 对应的 Handler
                $concreteHandler = $messageRouter->findHandler($receiveMessageResponse);
                $this->logger->info("$messageId $messageBodyMd5 ProcessMessage Start, " . get_class($concreteHandler));

                // 确认找到 Handler 可以从队列里删除这条消息了
                try {
                    // 直接调用deleteMessage即可。
                    $res = $this->queue->deleteMessage($receiptHandle);
                    $this->logger->debug("$messageId $messageBodyMd5 DeleteMessage Succeed!");

                    // 处理消息, 有异常重试处理3次
                    // Handler 内部异常由 Executor 处理
                    Common::repeatDeal(function() use ($messageRouter, $concreteHandler, $receiveMessageResponse) {
                        $messageRouter->processMessage($concreteHandler, $receiveMessageResponse);
                    }, 3, 100000, 'exception');
                    $this->logger->info("$messageId $messageBodyMd5 ProcessMessage Done!");

                } catch (MnsException $e) {
                    // 6. 这里CatchException并做异常处理
                    // 6.1 如果是receiptHandle已经过期，那么ErrorCode是MessageNotExist，表示通过这个receiptHandle已经找不到对应的消息。
                    // 6.2 为了保证receiptHandle不过期，VisibilityTimeout的设置需要保证足够消息处理完成。并且在消息处理过程中，也可以调用changeMessageVisibility这个函数来延长消息的VisibilityTimeout时间。
                    $this->logger->warning("$messageId $messageBodyMd5 DeleteMessage Failed!", [
                        'MnsErrorCode' => $e->getMnsErrorCode(),
                    ]);
                } catch (\Exception $ex) {
                    $this->logger->warning("$messageId $messageBodyMd5 ProcessMessage Failed!", [
                        'Ex' => $ex->getMessage() . "; On File:" . $ex->getFile() . ":" . $ex->getLine()
                    ]);
                } catch (\Error $err) {
                    $this->logger->warning("$messageId $messageBodyMd5 ProcessMessage Failed!", [
                        'ErrEx' => $err->getMessage() . "; On File:" . $err->getFile() . ":" . $err->getLine()
                    ]);
                }

            } catch (HandlerNotFound $e) {
                // 没有找到对应的处理器时,删除消息
                $this->queue->deleteMessage($receiptHandle);
                $this->logger->warning("$messageId $messageBodyMd5 ClearMessage Succeed!");
            }
        } catch (MnsException $e) {
            // 忽略 MessageNotExist 消息
            if ($e->getMnsErrorCode()  != 'MessageNotExist') {
                // 3. 像前面的CreateQueue和SendMessage一样，我们认为ReceiveMessage也是有可能出错的，所以这里加上CatchException并做对应的处理。
                $this->logger->warning("ReceiveMessage Failed", [
                    'MnsErrorCode' => $e->getMnsErrorCode(),
                ]);
            }
        }

    }
}
