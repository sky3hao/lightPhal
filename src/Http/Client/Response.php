<?php

namespace Tengyue\Infra\Http\Client;

class Response
{
    public $body = '';
    public $header = null;

    public function __construct()
    {
        $this->header = new Header();
    }
}