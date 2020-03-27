<?php

namespace Tengyue\Infra;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger as BaseLogger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Tengyue\Infra\Log\Log;
use Tengyue\Infra\Log\Exception;

/**
 * Class Logger
 *
 * @package Tengyue\Infra
 */
class Logger extends  Log
{
    /**
     * @var array
     */
    protected $options = [
        'name'          => "default",
        'logFile'       => "",
        'level'         => BaseLogger::DEBUG,
        'flushInterval' => 1,
//        'format'        => "%dateTime% [%levelName%] [%channel%] %messages% %context% \n",
        'format'        => '{"datatime":"%datetime%","level":"%levelName%","channel":"%channel%","message":"%messages%","context":%context%}' . "\n",
        'dateFormat'    => "Y-m-d H:i:s",
    ];

    /**
     * Logger constructor.
     *
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        if (!is_array($options)) {
            $options = [];
        }
        $this->options = array_merge($this->options, $options);
        if ($this->options['logFile'] == '') {
            throw new Exception(sprintf('The option of the logFile cannot be empty'));
        }

        parent::__construct($this->options['name']);

        $this->flushInterval = $this->options['flushInterval'];
        $this->createDir();

//        $stream = new StreamHandler($this->options['logFile'],  $this->options['level']);
//        $stream->setFormatter(new LineFormatter($this->options['format'], $this->options['dateFormat']));

        $stream = new StreamHandler($this->options['logFile'],  $this->options['level']);
        $stream->setFormatter(new JsonFormatter());

        $this->pushHandler($stream);

        return $this;
    }

    /**
     * Create dir
     *
     * @throws Exception
     */
    private function createDir()
    {
        $logFile = $this->options['logFile'];
        $dir = dirname($logFile);
        if ($dir !== null && !is_dir($dir)) {
            $status = mkdir($dir, 0777, true);
            if ($status === false) {
                throw new Exception(sprintf('There is no existing directory at "%s" and its not buildable: ', $dir));
            }
        }
    }

}