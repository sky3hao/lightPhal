<?php

namespace InfraTest;

use Tengyue\Infra\Security\Random;


/**
 * Class LoggerTest
 */
class RandomTest extends AbstractTestCase
{

    /**
     * @var Random
     */
    public $random;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->random = new Random();
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
         // Random binary string
        $random = $this->random;
         $bytes = $random->bytes();
         echo $bytes;

         // Random base62 string
         echo $random->base62(); // z0RkwHfh8ErDM1xw

         // Random base64 string
         echo $random->base64(12); // XfIN81jGGuKkcE1E
         echo $random->base64(12); // 3rcq39QzGK9fUqh8
         echo $random->base64();   // DRcfbngL/iOo9hGGvy1TcQ==
         echo $random->base64(16); // SvdhPcIHDZFad838Bb0Swg==

         // Random URL-safe base64 string
         echo $random->base64Safe();           // PcV6jGbJ6vfVw7hfKIFDGA
         echo $random->base64Safe();           // GD8JojhzSTrqX7Q8J6uug
         echo $random->base64Safe(8);          // mGyy0evy3ok
         echo $random->base64Safe(null, true); // DRrAgOFkS4rvRiVHFefcQ==

         // Random UUID
         echo $random->uuid(); // db082997-2572-4e2c-a046-5eefe97b1235
         echo $random->uuid(); // da2aa0e2-b4d0-4e3c-99f5-f5ef62c57fe2
         echo $random->uuid(); // 75e6b628-c562-4117-bb76-61c4153455a9
         echo $random->uuid(); // dc446df1-0848-4d05-b501-4af3c220c13d

         // Random number between 0 and $len
         echo $random->number(256); // 84
         echo $random->number(256); // 79
         echo $random->number(100); // 29
         echo $random->number(300); // 40

         // Random base58 string
         echo $random->base58();   // 4kUgL2pdQMSCQtjE
         echo $random->base58();   // Umjxqf7ZPwh765yR
         echo $random->base58(24); // qoXcgmw4A9dys26HaNEdCRj9
         echo $random->base58(7);  // 774SJD3vgP
    }


}