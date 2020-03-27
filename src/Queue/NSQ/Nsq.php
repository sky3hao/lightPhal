<?php

namespace Tengyue\Infra\Queue\NSQ;

use NSQClient\Access\Endpoint;
use NSQClient\Message\Message;
use NSQClient\Queue;
use Tengyue\Infra\Queue\Exception;

/**
 * Class Nsq
 *
 * @package Tengyue\Infra\Queue\NSQ
 *
 * <code>
 *
 * $config = [
 *     'lookupdAddr'   => ["http://127.0.0.1:4161"],
 * ];
 * $nsq = new \Tengyue\Infra\Queue\NSQ\Nsq($config);
 *
 * // publish
 * $nsq->pub("myTopic", "some words");
 * $nsq->pub("myTopic", ['a', 'b']);
 * $nsq->deferPub("myTopic", "msg", 5); // seconds
 *
 * // subscribe as daemon
 * // 消费者闭包里, 建议都try...catch... 并记录日志
 * $nsq->doWork("myTopic", "channel1", function($message) {
 *     try {
 *         $data = $message->data();
 *         // ...
 *     } catch (Exception) {
 *         // log etc.
 *     }
 * });
 *
 * </code>
 */
class Nsq
{
    /**
     * Max times of retry deal
     */
    const MAX_RETRY_TIMES = 3;

    /**
     * Retry interval
     */
    const RETRY_INTERVAL_SECOENDS = 3;

    /**
     * Suffix of failed topic
     */
    const FAILED_SUFFIX = '__FAILED__';

    /**
     * Endpoint
     *
     * @var Endpoint
     */
    protected $endpoint;

    /**
     * @var array
     */
    protected $options = [
        'lookupdAddr'   => ["http://127.0.0.1:4161"],
    ];

    /**
     * Nsq constructor.
     *
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        if (!is_array($options)) {
            $options = [];
        }
        if (!isset($options['lookupdAddr'])) {
            throw new Exception("Lacks the necessary configuration for NSQ service");
        }
        $this->options = array_merge($this->options, $options);

        $lookupdAddr = is_array($this->options['lookupdAddr'])
            ? $this->options['lookupdAddr'][array_rand($this->options['lookupdAddr'])]
            : $this->options['lookupdAddr'];
        $this->endpoint = new Endpoint($lookupdAddr);
    }

    /**
     * Publish
     *
     * @param $topic
     * @param $message
     * @return bool
     */
    public function pub($topic, $message)
    {
        $message = new Message($message);
        return Queue::publish($this->endpoint, $topic, $message);
    }

    /**
     * Deferred publish
     *
     * @param $topic
     * @param $message
     * @param $deferTimes
     * @return bool
     */
    public function deferPub($topic, $message, $deferTimes)
    {
        if ($deferTimes <= 3600) {
            $message = (new Message($message))->deferred($deferTimes);
        } else {
            $message = [
                'original' => $message,
                '__deferTs__' => time() + $deferTimes
            ];
            $message = (new Message($message))->deferred(3600);
        }

        return Queue::publish($this->endpoint, $topic, $message);
    }
    /**
     * Batch publish
     *
     * @param $topic
     * @param array $message
     * @return bool
     */
    public function batchPub($topic, array $message)
    {
        $message = \NSQClient\Message\Bag::generate($message);
        return Queue::publish($this->endpoint, $topic, $message);
    }

    /**
     * Subscribe and Control
     *
     * @param $topic
     * @param $channel
     * @param callable $callback
     */
    public function doWork($topic, $channel, callable $callback)
    {
        $self = $this;
        Queue::subscribe($this->endpoint, $topic, $channel, function (Message $message) use ($callback, $self, $topic) {
            try {
                $data = $message->data();
                if (isset($data['__deferTs__'])) {
                    $interval = $data['__deferTs__'] - time();
                    $self->deferPub($topic, $data['original'], $interval);
                } else {
                    call_user_func($callback, $message);
                }
                $message->done();
            } catch (\Exception $e) {
                if ($message->attempts() < $self::MAX_RETRY_TIMES) {
                    $message->delay($self::RETRY_INTERVAL_SECOENDS);
                } else {
                    $self->pub($topic . $self::FAILED_SUFFIX, $message->data());
                    $message->done();
                }
            }
        });
    }
}