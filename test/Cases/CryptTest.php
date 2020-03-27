<?php

namespace InfraTest;
use Tengyue\Infra\Crypt;


/**
 * Class LoggerTest
 */
class CryptTest extends AbstractTestCase
{

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
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
        $crypt = new Crypt();

        $crypt->setCipher('aes-256-ctr');

        $key  = "T4\xb1\x8d\xa9\x98\x05\\\x8c\xbe\x1d\x07&[\x99\x18\xa4~Lc1\xbeW\xb3";
        $text = "The message to be encrypted";

        $encrypted = $crypt->encrypt($text, $key);
        echo $encrypted;
        echo $crypt->decrypt($encrypted, $key);
    }


}