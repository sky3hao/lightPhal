<?php

namespace Tengyue\Infra\Http\TransProxy;

use Tengyue\Infra\Di\Injectable;
use Tengyue\Infra\Http\Client\Request;

/**
 * Class Proxy
 *
 * $this->proxy->get(url,array params,array headers)
 * $this->proxy->post(url,array params,array headers)
 *
 * @package Tengyue\Infra\ShuangShi
 */
class Proxy extends Injectable
{
    private $provider;

    /**
     * Call constructor
     *
     * $arrTrans 从配置文件获取的trans是一个二维数组
     * $arrTrans=[
     *      [
     *          "host"=>"10.88.88.2",
     *          "port"=>80,
     *      ],
     *      [
     *          "host"=>"10.88.88.3",
     *          "port"=>80,
     *      ]
     *  ]
     *
     * @param $key
     * @throws \LinkORB\Component\Etcd\Exception\KeyNotFoundException
     * @throws \Tengyue\Infra\Http\Client\Provider\Exception
     */
    public function __construct($key)
    {
        $this->provider = Request::getProvider();
        $arrTrans = json_decode($this->etcdClient->get($key), true);
        mt_srand(time());
        $trans = $arrTrans[mt_rand(0, count($arrTrans) - 1)];
        $this->provider->setProxy($trans['host'], $trans['port']);
    }

    /**
     * Restful API
     *
     * @param $func
     * @param array $arrArgs
     * @return mixed|null
     * @throws \Exception
     */
    public function __call($func, $arrArgs = [])
    {
        try {
            $this->provider->setBaseUri($arrArgs[0]);
            if (isset($arrArgs[2])) {
                $this->provider->header->setMultiple($arrArgs[2]);
            }
            return call_user_func_array(
                [
                    $this->provider,
                    $func
                ],
                [
                    $this->provider->getBaseUri(),
                    $arrArgs[1]
                ]
            );
        } catch (\Exception $e) {
            $this->logger->warning('[' . __CLASS__ . ']' . $e->getMessage(), $e->getCode());
            return null;
        }
    }
}
