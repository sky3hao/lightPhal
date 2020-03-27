<?php

namespace InfraTest;

use Tengyue\Infra\Etcd\Client;

class EtcdTest extends AbstractTestCase
{
    private $client;

    protected function setUp()
    {
        parent::setUp();
        $arrConfig = [
            "scheme" => "http",
            "host" => "127.0.0.1",
            "port" => 2379
        ];
        $this->client = Client::getInstance($arrConfig);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testGet()
    {
        $value = $this->client->get("node/php");
        $this->assertSame("192.168.0.1", $value);
    }

    public function testSet()
    {
        $key = "/node/java";
        $value = "192.168.1.128";
        $this->client->set($key, $value);
        $this->assertSame($value, $this->client->get($key));
    }
}
