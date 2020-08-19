<?php

namespace Core\Database\Connector;

use Core\BaseObject;

class Connector extends BaseObject
{
    public $useMaster = false;

    public $master = null;

    public $slave = null;

    public $statement = null;

    public $config = ['master' => [], 'slave' => []];

    public function __construct($config = [])
    {
        parent::__construct();

        // 检查配置
        $this->checkConfig($config);
        $this->config['master'] = array_merge($this->config['master'], $config['master']);
        $this->config['slave'] = array_merge($this->config['slave'], $config['slave']);
    }

    public function useMaster($isUse = false)
    {
        $this->useMaster = $isUse;
        return $this;
    }

    public function getDb()
    {
        $this->checkConnect();

        return $this->useMaster ? $this->master : $this->slave;
    }

    public function getPrefix()
    {
        $node = $this->useMaster ? 'master' : 'slave';

        return isset($this->config[$node]['prefix']) ?: '';
    }

    // 检查配置
    protected function checkConfig(array $config)
    {
        if (!isset($config['master']) || !isset($config['slave'])) {
            throw new \Exception("Config Error , doesn't exist config['master'] or config['slave']");
        }
        return $this;
    }

    public function createConnector()
    {
        $this->checkConfig($this->config);

        $this->master = $this->connect($this->config['master']);

        $this->slave = $this->connect($this->config['slave']);

        return $this;
    }

    public function setStatement($statement)
    {
        $this->statement = $statement;
        return $this;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function resetConnector()
    {
        $this->close();

        $this->createConnector();
    }

    // 关闭连接
    public function close() {
        if (!empty($this->master) && method_exists($this->master, 'close')) {
            $this->master->close();
        }
        if (!empty($this->slave) && method_exists($this->slave, 'close')) {
            $this->slave->close();
        }
        unset($this->master);
        unset($this->slave);
    }

    public function connect($config = [])
    {

    }
}