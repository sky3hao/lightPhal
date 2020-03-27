<?php

namespace Tengyue\Infra\Etcd;

use LinkORB\Component\Etcd\Client as EtcdClient;
use Tengyue\Infra\Exception;

/**
 * Class Client
 *
 * @package Tengyue\Infra\Etcd
 */
class Client extends EtcdClient
{
    /**
     * singleton
     *
     * @var Client
     */
    protected static $client = null;

    /**
     * get instance
     *
     * @param $config
     * @return Client
     * @throws Exception
     */
    public static function getInstance($config)
    {
        if (self::$client == null) {
            self::$client = new self($config);
        }
        return self::$client;
    }

    /**
     * Client constructor
     *
     * @param $arrConfig
     * @throws Exception
     */
    public function __construct($arrConfig)
    {
        if (empty($arrConfig) || !is_array($arrConfig)) {
            throw new Exception("etcd config is empty.");
        }
        $url = "{$arrConfig['scheme']}://{$arrConfig['host']}:{$arrConfig['port']}";
        parent::__construct($url);
    }
}
