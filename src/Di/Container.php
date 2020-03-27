<?php

namespace Tengyue\Infra\Di;

/**
 * Class Container
 * @package Tengyue\Infra\Di
 *
 * <code>
 * Container::getInstance()->logger->info("some words");
 * </code>
 */
class Container extends Injectable
{
    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}