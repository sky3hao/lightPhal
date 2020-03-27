<?php

namespace InfraTest;



class FilterTest extends AbstractTestCase
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
        $filter = new \Tengyue\Infra\Filter();

        echo $filter->sanitize("some(one)@exa\\mple.com", "email"); // returns "someone@example.com"
        echo $filter->sanitize("hello<<", "string"); // returns "hello"
        echo $filter->sanitize("!100a019", "int"); // returns "100019"
        echo $filter->sanitize("!100a019.01a", "float"); // returns "100019.01"

    }


}