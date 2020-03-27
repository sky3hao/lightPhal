<?php

namespace InfraTest;


use Tengyue\Infra\Cache\Backend\Redis;
use Tengyue\Infra\Cache\DBMemcache;
use Tengyue\Infra\Cache\DBRedis;
use Tengyue\Infra\Cache\Frontend\Data;

class CacheTest extends AbstractTestCase
{
    /**
     * @var Redis
     */
    protected $redis;

    protected $redisKey = "redisKey";

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $frontCache = new Data([
            "lifetime" => 86400
        ]);
        $this->redis = new Redis($frontCache, [
            'host'          => '127.0.0.1',
            'port'          => '6379',
            'persistent'    => false,
            'index'         => 0,
        ]);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function rediscache ()
    {
        $msg = ['a', [12, 21, ['b' => 2]]];
        $msg = new \stdClass();
        $msg->b = 'b';

        $rs = $this->redis->save($this->redisKey, $msg);
        $this->assertTrue($rs);
        $result = $this->redis->get($this->redisKey, $msg);
        //$this->assertContains($msg, $result);
    }

    /**
     * @test
     */
    public function dbrediscache()
    {
        $dbRedisKey = 'DBRedisKey';
        $options = [
            'host'          => '127.0.0.1',
            'port'          => '6379',
        ];
        $redis = new DBRedis($options);
        $redis->set($dbRedisKey, 'mm');
        $rs = $redis->get($dbRedisKey);
        $this->assertContains('mm', $rs);

        $key2 = 'DBR2';
        $redis->setMulti(['key1' => 'v1', 'key2'=>'v2'], 22);
        $rs2 = $redis->getMulti([$dbRedisKey, $key2, 'key1']);
        //var_dump($rs2);
    }

    /**
     * @test
     */
    public function dbredis()
    {
        try {
            $test = DBRedis::getInstance([
                'host' => '127.0.0.1',
                'port' => 6379,
//        'select' => 0,
                'timeout' => 2,
//        'maxRetry' => 3,
            ]);
            $r = $test->set("infra", 'v');
            //$r = $test->setMulti(["kkkd" => 'v', 'sss' => 'v2'], 2);
            var_dump($r);
        } catch (\Exception $e) {
            echo 'EX' . $e->getMessage();
        }
    }


}