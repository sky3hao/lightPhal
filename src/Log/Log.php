<?php

namespace Tengyue\Infra\Log;

use Monolog\Logger;
use Tengyue\Infra\Di\Container;

/**
 * Class Log
 *
 * @package Tengyue\Infra\Log
 */
class Log extends Logger
{
    /**
     * trace 日志级别
     */
    const TRACE = 650;

    /**
     * @var int 刷新日志条数
     */
    protected $flushInterval = 1;

    /**
     * @var array 日志数据记录
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $processors = [];

    /**
     * @var array 日志级别对应名称
     */
    protected static $levels = array(
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
        self::TRACE     => 'TRACE'
    );

    public function __construct($name = 'default')
    {
        parent::__construct($name);
    }

    /**
     * 记录日志
     *
     * @param int   $level   日志级别
     * @param mixed $message 信息
     * @param array $context 附加信息
     * @return bool
     */
    public function addRecord($level, $message, array $context = array())
    {
        $levelName = static::getLevelName($level);

        if (! static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ? : 'UTC');
        }

        // php7.1+ always has microseconds enabled, so we do not need this hack
        if ($this->microsecondTimestamps && PHP_VERSION_ID < 70100) {
            $ts = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone);
        } else {
            $ts = new \DateTime(null, static::$timezone);
        }
        $ts->setTimezone(static::$timezone);

        $requestId = Container::getInstance()->request->hasHeader("X-Request-Id")
            ? Container::getInstance()->request->getHeader("X-Request-Id")
            : "none";

        if ($this->hasInte($context)) {
            if (count($context) == 1) {
                $context = ['ctx' => array_values($context)[0]];
            } else if (count($context) > 1) {
                $context = ['ctx' => json_encode($context)];
            }
        }

        $message = $this->formatMessage($message);
//        $message = $this->getTrace($message);
        $record = $this->formateRecord($message, $requestId, $context, $level, $levelName, $ts, []);

        foreach ($this->processors as $processor) {
            $record = call_user_func($processor, $record);
        }

        $this->messages[] = $record;
        if (count($this->messages) >= $this->flushInterval) {
            $this->flushLog();
        }

        return true;
    }

    /**
     * 格式化一条日志记录
     *
     * @param string    $message   信息
     * @param string    $requestId
     * @param array     $context    上下文信息
     * @param int       $level     级别
     * @param string    $levelName 级别名
     * @param \DateTime $ts        时间
     * @param array     $extra     附加信息
     * @return array
     */
    public function formateRecord($message, $requestId, $context, $level, $levelName, $ts, $extra)
    {
        $record = array(
            'messages'      => $message,
            'requestId'     => $requestId,
            'context'       => $context,
            'level'         => $level,
            'levelName'     => $levelName,
            'channel'       => $this->name,
            'datetime'      => $ts,
            'extra'         => $extra,
        );

        return $record;
    }

    /**
     * 写入日志信息格式化
     *
     * @param $message
     * @return string
     */
    public function formatMessage($message)
    {
        if (is_array($message)) {
            return json_encode($message);
        }
        return $message;
    }

    /**
     * 计算调用trace
     *
     * @param $message
     * @return string
     */
    public function getTrace($message): string
    {
        $traces = debug_backtrace();
        $count = count($traces);
        $ex = '';
        if ($count >= 7) {
            $info = $traces[6];
            if (isset($info['file'], $info['line'])) {
                $filename = basename($info['file']);
                $lineNum = $info['line'];
                $ex = "$filename:$lineNum";
            }
        }
        if ($count >= 8) {
            $info = $traces[7];
            if (isset($info['class'], $info['type'], $info['function'])) {
                $ex .= ',' . $info['class'] . $info['type'] . $info['function'];
            } elseif (isset($info['function'])) {
                $ex .= ',' . $info['function'];
            }
        }

        if (!empty($ex)) {
            $message = "trace[$ex] " . $message;
        }


        return $message;
    }

    /**
     * 刷新日志到handlers
     */
    public function flushLog()
    {
        if (empty($this->messages)) {
            return;
        }

        reset($this->handlers);

        while ($handler = current($this->handlers)) {
            $handler->handleBatch($this->messages);
            next($this->handlers);
        }

        $this->messages = [];
    }

    /**
     * 添加一条trace日志
     *
     * @param string $message 日志信息
     * @param array $context 附加信息
     * @return bool
     */
    public function addTrace($message, array $context = array())
    {
        return $this->addRecord(static::TRACE, $message, $context);
    }

    /**
     *
     * @param array $arr
     * @return bool
     */
    private function hasInte(array $arr)
    {
        if (count($arr) > 0) {
            foreach ($arr as $k => $v) {
                if (gettype($k) == "integer") {
                    return true;
                }
            }
        }
        return false;
    }

}
