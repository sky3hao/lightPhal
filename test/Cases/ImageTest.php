<?php

namespace InfraTest;

use Tengyue\Infra\Image\Factory;


/**
 * Class ImageTest
 */
class ImageTest extends AbstractTestCase
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
     * @throws \Tengyue\Infra\Factory\Exception
     * @throws \Tengyue\Infra\Image\Exception
     */
    public function info()
    {
        $options = [
            "file"    => "test/Cases/kendo.jpg",
        ];
        $image = Factory::load($options);

        $image->resize(250, 500)->rotate(90)->crop(150, 150, 50)->blur(2)->text("KENDO", 50, 50, 100, 'FFFFFF', 38);

        $image2 = Factory::load($options);
        $image2->watermark($image, 20, 20);
        $image2->save("test/Cases/kendo3.jpg");

        if ($image->save("test/Cases/kendo2.jpg")) {
            echo 'success';
        }
    }


}