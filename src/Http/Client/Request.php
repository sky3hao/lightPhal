<?php

namespace Tengyue\Infra\Http\Client;

use Tengyue\Infra\Di\Container;
use Tengyue\Infra\Http\Client\Provider\Curl;
use Tengyue\Infra\Http\Client\Provider\Exception as ProviderException;
use Tengyue\Infra\Http\Client\Provider\Stream;
use Tengyue\Infra\Http\Uri;

/**
 * Class Request
 *
 * @package Tengyue\Infra\Http\Client
 */
abstract class Request
{
    protected $baseUri;
    public $header = null;

    const VERSION = '0.0.2';

    public function __construct()
    {
        $this->baseUri = new Uri();
        $this->header = new Header();

        $requestId = Container::getInstance()->request->hasHeader("X-Request-Id")
            ? Container::getInstance()->request->getHeader("X-Request-Id")
            : "none";
        $this->header->set('X-Request-Id', $requestId);
    }

    public static function getProvider()
    {
        if (Curl::isAvailable()) {
            return new Curl();
        }

        if (Stream::isAvailable()) {
            return new Stream();
        }

        throw new ProviderException("There isn't any available provider");
    }

    public function setBaseUri($baseUri)
    {
        $this->baseUri = new Uri($baseUri);
    }

    public function getBaseUri()
    {
        return $this->baseUri;
    }

    public function resolveUri($uri)
    {
        return $this->baseUri->resolve($uri);
    }
}

