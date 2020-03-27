<?php

namespace Tengyue\Infra\Data;

use Zeus\Data\Common\ConnectionConfig;
use Zeus\Data\MySQL\ConnectionNDWithUTF8mb4;
use Zeus\Data\ConnectorTransactionImpl;

/**
 * Class DBConnector
 *
 * @package Tengyue\Infra\Data
 */
class DBConnector extends ConnectorTransactionImpl
{
    private static $master = null;
    private static $slave = null;
    private static $backup = null;
    private static $transactionList = array();

    protected $options = [
        'master' => [
            'host'     => '127.0.0.1',
            'username' => 'user',
            'password' => 'password',
            'dbname'   => 'dbname',
            'persistent' => false,
        ],
        'slave' => [
            'slave1' => [
                'host'     => '127.0.0.1',
                'username' => 'user',
                'password' => 'password',
                'dbname'   => 'dbname',
                'persistent' => false,
            ],
            'slave2' => [
                'host'     => '127.0.0.1',
                'username' => 'user',
                'password' => 'password',
                'dbname'   => 'dbname',
                'persistent' => false,
            ],
        ],
        'backup' => [
            'host'     => '127.0.0.1',
            'username' => 'user',
            'password' => 'password',
            'dbname'   => 'dbname',
            'persistent' => false,
        ],
    ];

    /**
     * DBConnector constructor.
     *
     * @param array $options
     */
    public final function __construct($options = [])
    {
        if (!is_array($options)) {
            $options = [];
        }
        $this->options = array_merge($this->options, $options);
    }

    public function getMasterConf()
    {
        return $this->ConnConfig($this->options['master']);
    }

    public function getSlaveConf()
    {
        $config = $this->options['slave'];
        return $this->ConnConfig($config[array_rand($config)]);
    }

    public function getBackupConf()
    {
        return $this->ConnConfig($this->options['backup']);
    }

    public function getTransactionConf()
    {
        return $this->ConnConfig($this->options['master']);
    }

    private function ConnConfig(array $cnf)
    {
        $config = new ConnectionConfig();
        $config->setHost($cnf['host']);
        $config->setDbName($cnf['dbname']);
        $config->setUserName($cnf['username']);
        $config->setPassword($cnf['password']);
        $config->setPersistent($cnf['persistent']);

        return $config;
    }

    /**
     *
     *
     * @return null|\Zeus\Data\Common\IConnection|ConnectionNDWithUTF8mb4
     * @throws \Zeus\Data\Exception\DatabaseException
     */
    public function getMasterConnection()
    {
        if (self::$master === null) {
            self::$master = new ConnectionNDWithUTF8mb4($this->getMasterConf());
        }
        return self::$master;
    }

    /**
     *
     *
     * @return null|\Zeus\Data\Common\IConnection|ConnectionNDWithUTF8mb4
     * @throws \Zeus\Data\Exception\DatabaseException
     */
    public function getSlaveConnection()
    {
        if (self::$slave === null) {
            self::$slave = new ConnectionNDWithUTF8mb4($this->getSlaveConf());
        }
        return self::$slave;
    }

    /**
     *
     *
     * @return null|\Zeus\Data\Common\IConnection|ConnectionNDWithUTF8mb4
     * @throws \Zeus\Data\Exception\DatabaseException
     */
    public function getBackupConnection()
    {
        if (self::$backup === null) {
            self::$backup = new ConnectionNDWithUTF8mb4($this->getBackupConf());
        }
        return self::$backup;
    }

    /**
     *
     *
     * @param $tag
     * @return mixed
     * @throws \Zeus\Data\Exception\DatabaseException
     */
    public function getTransactionConnection($tag)
    {
        if (!isset(self::$transactionList[$tag])) {
            self::$transactionList[$tag] = new ConnectionNDWithUTF8mb4($this->getTransactionConf());
        }
        return self::$transactionList[$tag];
    }

}