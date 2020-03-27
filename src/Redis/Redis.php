<?php

namespace Tengyue\Infra\Redis;

use Tengyue\Infra\Helper\Common;

/**
 * Redis Extended
 *
 * Call fails reconnection
 * Availability condition monitoring
 */
class Redis
{
    /**
     * singleton
     *
     * @var \Redis
     */
    protected static $instance;

    /**
     * 配置
     *
     * @var array
     */
    protected $arrConf;

    /**
     * redis链接
     *
     * @var \Redis
     */
    protected $redis;

    /**
     * 调用失败重试次数
     *
     * @var integer
     */
    protected $maxRetry = 2;

    /**
     * @var int
     */
    protected $retryInterval = 200000;

    /**
     * 可用状态
     *
     * @var bool
     */
    protected $usable = true;

    /**
     * get instance method
     *
     * @param $config
     * @return \Redis|Redis
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
     * Redis constructor.
     *
     * @param $arrConfig
     * @throws \Exception
     */
    private function __construct($arrConfig)
    {
        if (empty($arrConfig) || !is_array($arrConfig)) {
            throw new Exception("Redis config is empty.");
        }

        $this->arrConf = $arrConfig;
        if (isset($arrConfig['maxRetry'])) {
            $this->maxRetry = $arrConfig['maxRetry'];
        }
        if (isset($arrConfig['retryInterval'])) {
            $this->maxRetry = $arrConfig['retryInterval'];
        }

        $this->redis = new \Redis();

        Common::repeatDeal(\Closure::bind(function() {
            return $this->connect();
        }, $this), $this->maxRetry, $this->retryInterval);
    }

    /**
     * 调用redis方法
     *
     * @param $method
     * @param $arrArgs
     * @return bool|mixed
     * @throws Exception
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
        if ($this->usable === false) {
            throw new Exception("Redis calling error, retry times: " . $this->maxRetry);
        }

        return $mixRet;
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
            }, $this), $this->maxRetry, $this->retryInterval);
        }
    }

    /**
     * is usable
     *
     * @return bool
     */
    public function isUsable()
    {
        return $this->usable;
    }

    /**
     * 遍历配置进行重新链接
     *
     * @return bool
     * @throws Exception
     */
    protected function connect()
    {
        if (array_key_exists('persistent', $this->arrConf) && $this->arrConf['persistent']) {
            $bol = $this->redis->pconnect($this->arrConf['host'], $this->arrConf['port'], $this->arrConf['timeout']);
        } else {
            $bol = $this->redis->connect($this->arrConf['host'], $this->arrConf['port'], $this->arrConf['timeout']);
        }

        if ($bol === true) {
            if (isset($this->arrConf["auth"]) && !empty($this->arrConf["auth"])) {
                if (!$this->redis->auth($this->arrConf["auth"])) {
                    throw new Exception("Failed to authenticate with the Redisd server");
                }
            }

            if (array_key_exists('index', $this->arrConf)) {
                $this->redis->select($this->arrConf['index']);
            }
        }

        if (!$bol) {
            $this->usable = false;
        }

        return $bol;
    }
}
