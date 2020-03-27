<?php

namespace Tengyue\Infra\Cli;

use Tengyue\Infra\Di\Injectable;

/**
 * 任务路由控制类
 *
 * @namespace Cli
 */
class Console extends Injectable
{
    /**
     * 任务配置信息
     * 
     * @var TaskParamer
     */
    protected $taskParamer = null;

    /**
     * @var string
     */
    protected $baseNamespace = '';

    /**
     * 任务控制台初始化
     *
     * @param array $argv
     * @throws \Exception
     */
    public function __construct($argv)
    {
//        if (empty($argv) || !is_array($argv)) {
//            throw new \Exception("Illegal arguments!");
//        }
        
        $params = array();
        $module = $task = $action = '';
        foreach ($argv as $key => $arg) {
            $arg = trim($arg);

            if ($key == 0) {
                $parts = explode('/', $arg);
                @list($module, $task, $action) = $parts;
                continue;
            }

            $tmp = explode('=', $arg);
            $len = count($tmp);
            if ($len == 1) {
                $params[] = $tmp[0];
                continue;
            }
            if ($len == 2) {
                $params['kvMode'][$tmp[0]] = $tmp[1];
                continue;
            }
            if ($len > 2) {
                continue;
            }
        }

        if (empty($module)) {
            throw new \Exception("task module not set");
        }

        $this->taskParamer = new TaskParamer($module, $task, $action);
        $this->taskParamer->setParams($params);
    }

    /**
     * Set base namespace
     *
     * @param $name
     * @return $this
     */
    public function setBaseNamespace($name)
    {
        $this->baseNamespace = $name;
        return $this;
    }

    /**
     * 执行入口
     *
     * @throws Exception
     * @throws \ReflectionException
     */
    public function handle()
    {
        $path = $this->taskParamer->getClassPath();
        if (!file_exists($path)) {
            throw new Exception("file not exists:{$path}");
        }
        
        require_once $path;
        
        $taskClass = $this->taskParamer->getClassName($this->baseNamespace);
        if (!class_exists($taskClass)) {
            throw new Exception("Class:{$taskClass} does not exist!");
        }
        
        $actionName = $this->taskParamer->getActionName();
        if (!is_callable(array($taskClass, $actionName))) {
            throw new Exception('Action:' . $actionName . ' does not callable!');
        }

        $params = $this->taskParamer->getParams();
        $reflection = new \ReflectionMethod($taskClass, $actionName);
        if (array_key_exists('kvMode', $params)) {
            $fireArgs = array();
            $params = $params['kvMode'];
            foreach ($reflection->getParameters() AS $arg) {
                if (array_key_exists($arg->name, $params)) {
                    $fireArgs[$arg->name] = $params[$arg->name];
                } else {
                    if ($arg->isDefaultValueAvailable()) {
                        $fireArgs[$arg->name] = $arg->getDefaultValue();
                    } else {
                        throw new Exception('Missing parameter:' . $arg->name);
                    }
                }
            }
            $params = $fireArgs;
        }

        $task = new $taskClass;
        call_user_func_array(array($task, $actionName), $params);

        exit(0);
    }

}