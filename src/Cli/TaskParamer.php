<?php

namespace Tengyue\Infra\Cli;

/**
 * 任务参数信息
 *
 * @namespace Cli
 */
class TaskParamer
{
    /**
     * 默认的模块名
     *
     * @var string
     */
    const DEFAULT_MOUDULE = 'Script';

    /**
     * 默认的任务名
     *
     * @var string
     */
    const DEFAULT_TASK = 'Main';
    
    /**
     * 默认的方法名
     *
     * @var string
     */
    const DEFAULT_ACTION = 'index';
    
    /**
     * 任务名后缀
     *
     * @var string
     */
    const TASK_SUFFIX = 'Task';
    
    /**
     * 方法名后缀
     *
     * @var string
     */
    const ACTION_SUFFIX = 'Action';

    /**
     * 模块名
     *
     * @var string
     */
    protected $moduleName;

    /**
     * 任务名
     *
     * @var string
     */
    protected $taskName;
    
    /**
     * 方法名
     *
     * @var string
     */
    protected $actionName;
    
    /**
     * 方法的参数
     *
     * @var array
     */
    protected $params;
    
    /**
     * TaskParamer实例化
     *
     * @param string $module
     * @param string $task
     * @param string $action
     */
    public function __construct($module = self::DEFAULT_MOUDULE, $task = self::DEFAULT_TASK, $action = self::DEFAULT_ACTION)
    {
        $this->moduleName = ucfirst($module);
        $this->taskName = ucfirst($task);
        $this->actionName = $action;
    }
    
    /**
     * 设置参数值
     * 
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }
    
    /**
     * 获取参数值
     * 
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
    
    /**
     * 获取当前的类名
     *
     * @param string $baseNamespace
     * @return string
     */
    public function getClassName($baseNamespace)
    {
        $module = empty($this->moduleName) ? self::DEFAULT_MOUDULE : $this->moduleName;
        return self::concatNamespace($baseNamespace, $module, $this->getTaskName());
    }
    
    /**
     * 获取当前类所在的文件路径
     * 
     * @return string
     */
    public function getClassPath()
    {
        $module = empty($this->moduleName) ? self::DEFAULT_MOUDULE : $this->moduleName;
        $task = empty($this->taskName) ? self::DEFAULT_TASK : $this->taskName;
        return self::concatPath(
            APP_PATH,
            'Tasks',
            $module,
            $task  . self::TASK_SUFFIX . '.php'
        );
    }
    
    /**
     * 获取执行动作的方法名
     * 
     * @return string
     */
    public function getActionName()
    {
        if (empty($this->actionName)) {
            return self::DEFAULT_ACTION . self::ACTION_SUFFIX;
        }
        return $this->actionName . self::ACTION_SUFFIX;
    }
    
    /**
     * 获取任务名
     * 
     * @return string
     */
    public function getTaskName()
    {
        if (empty($this->taskName)) {
            return self::DEFAULT_TASK . self::TASK_SUFFIX;
        }
        return $this->taskName . self::TASK_SUFFIX;
    }

    /**
     * 连接参数为实际的路径地址
     *
     * @param string|array $path
     * @param string $_ 更多的路径参数
     * @return string 完整的路径地址
     */
    public static function concatPath($path, $_ = null)
    {
        $params = func_get_args();
        $parts = self::getMutiArray($params);
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * 连接参数为实际的命名空间类名
     *
     * @param string|array $name
     * @param string $_ 更多的参数
     * @return string 命名空间类名
     */
    public static function concatNamespace($name, $_ = null)
    {
        $params = func_get_args();
        $parts = self::getMutiArray($params);
        return implode('\\', $parts);
    }

    /**
     * 获取混杂的数组结构
     *
     * @param array $params
     * @return array
     */
    private static function getMutiArray($params)
    {
        $parts = [];
        foreach ($params as $p) {
            if (is_array($p)) {
                $parts = array_merge($parts, $p);
            } elseif ($p !== '' && $p != null) {
                $parts[] = $p;
            }
        }
        return $parts;
    }
}