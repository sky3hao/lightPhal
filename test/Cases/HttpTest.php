<?php

namespace InfraTest;
use Tengyue\Infra\Exception;
use Tengyue\Infra\Helper\Common;
use Tengyue\Infra\Http\Request;


/**
 * Class LoggerTest
 */
class HttpTest extends AbstractTestCase
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

        Common::handleError();

        $req = new Request();

        //fopen("/asdfa/asdf", "r");
        //throw new Exception("bb");

        var_dump($req->getServer("HTTP_HOST")); // Retrieve SERVER variables
        var_dump($req->getMethod());            // GET, POST, PUT, DELETE, HEAD, OPTIONS, PATCH, PURGE, TRACE, CONNECT
        var_dump($req->getServerAddress());
        var_dump($req->getURI());
        var_dump($req->isPut());
        var_dump($req->isGet());
        var_dump($req->getJsonRawBody());
        var_dump($req->getRawBody());

    }


}