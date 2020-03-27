<?php

namespace Tengyue\Infra\Helper;

/**
 * Class Common
 *
 * @package Tengyue\Infra\Helper
 */
class Common
{
    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function camelize($scored)
    {
        return ucfirst(implode('', array_map('ucfirst', array_map('strtolower', explode('_', $scored)))));
    }

    public static function memstr($haystack, $needle)
    {
        if (strpos($haystack, $needle) !== false) {
            return 1;
        }
        return 0;
    }

    /**
     * Set handle error
     */
    public static function handleError()
    {
        set_error_handler(function ($type, $message, $file, $line) {
            if (error_reporting() & $type) {
                throw new \ErrorException($message, $type, 0, $file, $line);
            }
        });
    }

    /**
     * 通用的重试方法
     *
     * @param callable $func    回调
     * @param int $retry        重试次数
     * @param int $delay        延迟间隔, 单位: 微秒
     * @param string $type      类型
     * @return bool|mixed
     * @throws \Exception
     * @throws \Error
     */
    public static function repeatDeal($func, $retry = 1, $delay = 0, $type = 'normal')
    {
        switch ($type) {
            case 'normal':
                try {
                    $rs = call_user_func($func);
                    if ($rs === false) {
                        usleep($delay);
                        $retry--;
                        if ($retry > -1) {
                            return self::repeatDeal($func, $retry, $delay, $type);
                        }
                    }
                    return $rs;
                } catch (\Exception $ex) {
                    usleep($delay);
                    $retry--;
                    if ($retry > -1) {
                        return self::repeatDeal($func, $retry, $delay, $type);
                    }
                    throw $ex;
                }
                break;
            case 'exception':
                try {
                    return call_user_func($func);
                } catch (\Exception $ex) {
                    usleep($delay);
                    $retry--;
                    if ($retry > -1) {
                        return self::repeatDeal($func, $retry, $delay, $type);
                    }
                    throw $ex;
                } catch (\Error $err) {
                    usleep($delay);
                    $retry--;
                    if ($retry > -1) {
                        return self::repeatDeal($func, $retry, $delay, $type);
                    }
                    throw $err;
                }
                break;
            case 'bool':
                $rs = call_user_func($func);
                if ($rs == false) {
                    usleep($delay);
                    $retry--;
                    if ($retry > -1) {
                        return self::repeatDeal($func, $retry, $delay, $type);
                    }
                }
                return $rs;
                break;
        }
        return false;
    }

    /**
     * 简单的异常信息
     *
     * @param \Exception | \Error $e
     * @return string
     */
    public static function simpleEx($e)
    {
        return get_class($e) . ": " . $e->getMessage() . "; On File:" . $e->getFile() . ":" . $e->getLine();
    }

    /**
     * 判断字符串是否为JSON
     *
     * @param string $string
     * @return bool
     */
    public static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}