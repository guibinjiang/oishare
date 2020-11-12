<?php

namespace Core\Factory;

use Core\BaseObject;
use Core\Conf;
use Core\Database\Connector\CoMysqlConnector;
use Core\Database\Connector\Connector;
use Core\Database\Connector\MysqlConnector;
use Core\Register;

class Factory
{
    public static function mysql($configNode = null, $useCoroutine = true)
    {
        $connector = $useCoroutine ? CoMysqlConnector::class : MysqlConnector::class;
        $base_name = basename(str_replace('\\', '/', $connector));
        $nodeName = "mysql.{$base_name}." . $configNode;
        $mysql = self::register()->get($nodeName);
        if (is_null($mysql)) {
            $conf = Conf::get('Mysql', $configNode);
            /** @var Connector $mysql */
            $mysql = (new $connector($conf))->createConnector();
            self::register()->set($nodeName, $mysql);
        }
        return $mysql;
    }

    /**
     * @return Register
     */
    public static function register()
    {
        return _class(Register::class);
    }
}
