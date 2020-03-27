<?php

namespace Tengyue\Infra\Queue\NSQ;

/**
 * Interface MessageInterface
 *
 * @package Tengyue\Infra\NSQ
 */
interface MessageInterface
{
    /**
     * Get message ID
     *
     * @return int
     */
    public function id();

    /**
     * Get message payload (raw)
     *
     * @return string
     */
    public function payload();

    /**
     * Get message data (serialized/un-serialized)
     *
     * @return mixed
     */
    public function data();

    /**
     * Get attempts
     *
     * @return int
     */
    public function attempts();

    /**
     * Get timestamp
     *
     * @return int
     */
    public function timestamp();

    /**
     * Make msg is done
     */
    public function done();

}