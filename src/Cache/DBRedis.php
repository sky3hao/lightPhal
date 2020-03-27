<?php

namespace Tengyue\Infra\Cache;

use Tengyue\Infra\Helper\Common;
use Zeus\Cache\ICache;

/**
 * Class DBRedis
 *
 * @package Tengyue\Infra\Cache
 */
class DBRedis implements ICache
{
    const CONNECT_TIMEOUT = 3;
    const AGAIN_CONNECT_TIME = 3;
    const RETRY_INTERVAL = 200000;

    /**
     * instance
     *
     * @var \Redis
     */
    protected static $instance;

    /**
     * redis链接
     *
     * @var \Redis
     */
    protected $redis;

    /**
     * @var array
     */
    private $options;

    /**
     * Get instance
     *
     * @param $config
     * @return \Redis|DBRedis
     * @throws \Exception
     */
    public static function getInstance($config)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * DBRedis constructor.
     *
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        if (!is_array($options)) {
            $options = [];
        }

        if (!isset($options["host"])) {
            $options["host"] = "127.0.0.1";
        }
        if (!isset($options["port"])) {
            $options["port"] = 6379;
        }
        if (!isset($options["auth"])) {
            $options["auth"] = "";
        }
        if (!isset($options["index"])) {
            $options["index"] = 0;
        }
        $this->options = $options;

        $this->redis = new \Redis();

        Common::repeatDeal(\Closure::bind(function() {
            return $this->connect();
        }, $this, DBRedis::class), self::AGAIN_CONNECT_TIME, self::RETRY_INTERVAL);
    }

    /**
     * Connect
     *
     * @return bool
     * @throws Exception
     */
    public function connect()
    {
        $bol = $this->redis->connect($this->options['host'], $this->options['port'], self::CONNECT_TIMEOUT);

        if ($bol === true) {
            if (isset($this->options["auth"]) && !empty($this->options["auth"])) {
                $auth = $this->options["auth"];
                $success = $this->redis->auth($auth);

                if (!$success) {
                    throw new Exception("Failed to authenticate with the Redisd server");
                }
            }

            if (isset($this->options["index"]) && $this->options["index"] > 0) {
                $success = $this->redis->select($this->options["index"]);

                if (!$success) {
                    throw new Exception("Redis server selected database failed");
                }
            }
        }

        return $bol;
    }

    /**
     * Ping & Reconnect
     *
     * @throws \Exception
     */
    public function pingAndReconnect()
    {
        try {
            $this->redis->ping();
        } catch (\Exception $e) {
            Common::repeatDeal(\Closure::bind(function() {
                return $this->connect();
            }, $this, DBRedis::class), self::AGAIN_CONNECT_TIME, self::RETRY_INTERVAL);
        }
    }

    /**
     * 调用redis方法
     *
     * @param $method
     * @param $arrArgs
     * @return bool|mixed
     * @throws \Exception
     */
    public function __call($method, $arrArgs)
    {
        if (!method_exists('Redis', $method)) {
            throw new Exception("Not exist method in Redis class");
        }

        try {
            $mixRet = call_user_func_array([$this->redis, $method], $arrArgs);
        } catch (\Exception $e) {
            $this->pingAndReconnect();
            $mixRet = call_user_func_array([$this->redis, $method], $arrArgs);
        }

        return $mixRet;
    }

    /**
     * @param mixed $key
     * @return bool|string|mixed
     * @throws \Exception
     */
    public function get($key)
    {
        try {
            return $this->redis->get($key);
        } catch (\Exception $e) {
            $this->pingAndReconnect();
            return $this->redis->get($key);
        }
    }

    /**
     * @param array $keys
     * @return array
     * @throws \Exception
     */
    public function getMulti(array $keys)
    {
        try {
            return $this->redis->mGet($keys);
        } catch (\Exception $e) {
            $this->pingAndReconnect();
            return $this->redis->mGet($keys);
        }
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param mixed $expire
     * @return bool|mixed
     * @throws \Exception
     */
    public function set($key, $value, $expire = null)
    {
        try {
            return $this->redis->set($key, $value, $expire);
        } catch (\Exception $e) {
            $this->pingAndReconnect();
            return $this->redis->set($key, $value, $expire);
        }
    }

    /**
     * @param array $dataSet
     * @param $expire
     * @throws \Exception
     */
    public function setMulti(array $dataSet, $expire = null)
    {
        try {
            $this->redis->mset($dataSet);
            if (!is_null($expire)) {
                $this->redis->multi(\Redis::PIPELINE);
                $keys = array_keys($dataSet);
                foreach ($keys as $key) {
                    $this->redis->expire($key, $expire);
                }
                $this->redis->exec();
            }
        } catch (\Exception $e) {
            $this->pingAndReconnect();
            $this->redis->mset($dataSet);
            if (!is_null($expire)) {
                $this->redis->multi(\Redis::PIPELINE);
                $keys = array_keys($dataSet);
                foreach ($keys as $key) {
                    $this->redis->expire($key, $expire);
                }
                $this->redis->exec();
            }
        }
    }

    /**
     * @param mixed $key
     * @return int
     * @throws \Exception
     */
    public function delete($key)
    {
        try {
            return $this->redis->delete($key);
        } catch (\Exception $e) {
            $this->pingAndReconnect();
            return $this->redis->delete($key);
        }
    }
}