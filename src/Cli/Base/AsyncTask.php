<?php

namespace Tengyue\Infra\Cli\Base;

use Tengyue\Infra\Queue\MNS\AsyncMessageRouter;
use Tengyue\Infra\Queue\MNS\Common\Executor;

/**
 *
 *<code>
 *
 * class MainTask extends \Tengyue\Infra\Cli\AsyncTask
 * {
 *
 *     public function handlerBag()
 *     {
 *          return [
 *              new TestHandler(),
 *              new TwoHandler()
 *          ];
 *     }
 * }
 *
 * // then
 * $ php bin/cli async/main/index
 *
 *</code>
 */
abstract class AsyncTask extends Task
{

    public function indexAction()
    {
        $router = AsyncMessageRouter::getInstance();

        $bag = $this->handlerBag();
        foreach ($bag as $v) {
            $router->registerHandler($v);
        }

        $executor = new Executor($router);
        $executor->run();
    }

    /**
     * @return array
     */
    abstract public function handlerBag();

}