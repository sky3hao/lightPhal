<?php

namespace InfraTest;

use Tengyue\Infra\Exception;
use Tengyue\Infra\Logger;


/**
 * Class LoggerTest
 */
class LoggerTest extends AbstractTestCase
{

    protected $logger;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = new Logger(['logFile' => '/tmp/new2.log']);
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
    public function info()
    {
        $infoMessage = 'Test Info Message';
        $this->logger->error("My log", ['options'=> 123, 'bb' => [123, 132, 13 , 'b' => 'bb']]);
        $this->logger->debug("debug");
        $result = $this->logger->info($infoMessage);
        $this->assertTrue($result);
        $this->assertContains($infoMessage, $this->getNoticeLog());
    }

    /**
     * @test
     */
//    public function error()
//    {
//        $errorMessage = 'Test Error Message';
////        $result = $this->logger->error($errorMessage . 1);
////        $this->assertTrue($result);
////        $this->assertContains($errorMessage . 1, $this->getErrorLog());
////        $result = $this->logger->error($errorMessage . 2);
////        $this->assertTrue($result);
////        $this->assertContains($errorMessage . 2, $this->getErrorLog());
//    }

}