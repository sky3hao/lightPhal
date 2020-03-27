<?php

namespace Tengyue\Infra\Queue\MNS\MNS;

use AliyunMNS\Client;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Exception\MnsException;
use Tengyue\Infra\Queue\MNS\Common\IProducer;
use Tengyue\Infra\Queue\MNS\Common\SendMessageResponse;

class Producer implements IProducer
{
    private $client;
    private $queue;

    public function __construct($endPoint, $accessId, $accessKey, $queueName)
    {
        $this->client = new Client($endPoint, $accessId, $accessKey);
        $this->queue = $this->client->getQueueRef($queueName);
    }

    /**
     * @param string $messageBody
     * @return SendMessageResponse
     */
    public function sendMessage($messageBody)
    {
        $request = new SendMessageRequest(
            $messageBody
        );

        try {
            $res = $this->queue->sendMessage($request);
            // 3. 消息发送成功
            $response = new SendMessageResponse();
            $response->setIsSucceed($res->isSucceed());
            $response->setStatusCode($res->getStatusCode());
            $response->setMessageId($res->getMessageId());
            $response->setMessageBodyMD5($res->getMessageBodyMD5());
            return $response;
        } catch (MnsException $e) {
            // 4. 可能因为网络错误，或MessageBody过大等原因造成发送消息失败，这里CatchException并做对应的处理。
            // echo "SendMessage Failed: " . $e . "\n";
            // echo "MNSErrorCode: " . $e->getMnsErrorCode() . "\n";
            throw $e;
        }
    }

    /**
     * @param string $messageBody
     * @param int $intervalMilliSecond
     * @return SendMessageResponse
     */
    public function sendDelayProcessingMessage($messageBody, $intervalMilliSecond)
    {
        $intervalSecond = round($intervalMilliSecond / 1000);
        // 阿里云延迟消息必须大于等于1秒
        if ($intervalSecond <= 0) {
            $intervalSecond = 1;
        }

        $request = new SendMessageRequest(
            $messageBody, $intervalSecond
        );

        try {
            $res = $this->queue->sendMessage($request);
            // 3. 消息发送成功
            $response = new SendMessageResponse();
            $response->setIsSucceed($res->isSucceed());
            $response->setStatusCode($res->getStatusCode());
            $response->setMessageId($res->getMessageId());
            $response->setMessageBodyMD5($res->getMessageBodyMD5());
            return $response;
        } catch (MnsException $e) {
            // 4. 可能因为网络错误，或MessageBody过大等原因造成发送消息失败，这里CatchException并做对应的处理。
            // echo "SendMessage Failed: " . $e . "\n";
            // echo "MNSErrorCode: " . $e->getMnsErrorCode() . "\n";
            throw $e;
        }
    }

}