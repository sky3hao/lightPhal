<?php

namespace Tengyue\Infra\Cli\Base;

use Tengyue\Infra\Cli\TaskInterface;
use Tengyue\Infra\Di\Injectable;

/**
 *
 *<code>
 * class HelloTask extends \Tengyue\Infra\Cli\Task\Base
 * {
 *     // This action will be executed by default
 *     public function indexAction()
 *     {
 *
 *     }
 *
 *     public function findAction()
 *     {
 *
 *     }
 * }
 *</code>
 */
abstract class Task extends Injectable implements TaskInterface
{

    /**
     * Tengyue\Infra\Cli\Task constructor
     */
    public final function __construct()
    {
        if (method_exists($this, "onConstruct")) {
            $this->onConstruct();
        }
    }

    /**
     * 默认的任务入口
     */
    public function indexAction()
    {
        echo "\nThis is the default action method.\n";
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        exit(0);
    }
}