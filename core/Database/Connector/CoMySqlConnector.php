<?php

namespace Core\Database\Connector;

class CoMysqlConnector extends Connector implements ConnectorIFace
{
    public function dsn($conf = [])
    {
        $conf['fetch_mode'] = true;
        return $conf;
    }

    public function connect($config = null, $times = 3)
    {
        // 创建协程mysql
        $coMysql = new \Swoole\Coroutine\MySQL();
        // 连接
        $connected = $coMysql->connect($this->dsn($config));
        // 重连
        if (!$connected && $times > 0) {
            $this->connect($config, --$times);
        }
        // 检查错误
        if ($coMysql->connect_errno || $times <= 0) {
            throw new \Exception(sprintf('Connect Error [%d]: %s', $coMysql->connect_errno, $coMysql->connect_error));
        }

        return $coMysql;
    }

    public function queryOne($sql = '', $bind = [])
    {
        $res = $this->query($sql, $bind);
        if(!empty($res) && is_array($res)) {
            return current(current($res));
        }
        return false;
    }

    public function queryRow($sql = '', $bind = [])
    {
        $res = $this->query($sql, $bind);
        if(!empty($res) && is_array($res)) {
            return current($res);
        }
        return false;
    }

    public function queryCol($sql = '', $bind = [], $field = '')
    {
        $res = $this->query($sql, $bind);
        $return = [];
        if(!empty($res) && is_array($res)) {
            $return = [];
            foreach($res as $key => $val){
                $return[] = isset($val[$field])?$val[$field]:current($val);
            }
        }
        return $return;
    }

    public function queryAll($sql = '', $bind = [])
    {
        $res = $this->query($sql, $bind = []);

        return $res;
    }

    public function query($sql = null, $bindings = [])
    {
        if (empty($sql)) {
            throw new \Exception('');
        }

        $statement = $this->prepare($sql, $bindings);

        if ($statement === false) {
            throw new \Exception('');
        }

        $this->setStatement($statement);

        return $this->fetchAll();
    }

    public function fetchAll()
    {
        return $this->getStatement()->fetchAll();
    }

    public function affectedRows()
    {
        return $this->getStatement()->affectedRows();
    }

    public function lastInsertId()
    {
        return $this->getStatement()->rowCount();
    }

    public function prepare($sql = '', $bind = [])
    {
        if (empty($sql)) {
            throw new \Exception();
        }
        // 断线重连一次
        for ($i = 0; $i < 2; $i++) {
            $db = $this->getDb();
            $statement = $db->prepare($sql);
            if ($statement === false && $i == 0)
            {
                if ($db->errno == 2006 || $db->errno == 2013) {
                    $this->resetConnector();
                    continue;
                }
            }
            break;
        }

        // 返回 statement 对象或则 FALSE ，又或者抛出错误
        if ($statement === false) {
            throw new \Exception('');
        }

        if ($statement->execute($bind) === false) {
            throw new \Exception('');
        }

        return $this->setStatement($statement);
    }

    public function checkConnect()
    {

    }

    public function selectType($val = '')
    {
        switch (true)
        {
            case is_bool($val):
                $type = \PDO::PARAM_BOOL;
                break;
            case is_int($val):
                $type = \PDO::PARAM_INT;
                break;
            case is_null($val):
                $type = \PDO::PARAM_NULL;
                break;
            default:
                $type = \PDO::PARAM_STR;
                break;
        }

        return $type;
    }
}