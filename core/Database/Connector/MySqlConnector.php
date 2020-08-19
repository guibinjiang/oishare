<?php

namespace Core\Database\Connector;

use Core\BaseObject;

class MysqlConnector extends Connector implements ConnectorIFace
{
    public function dsn($conf = [])
    {
        $dsn = "mysql:dbname={$conf['database']};host={$conf['host']};port={$conf['port']};charset={$conf['charset']}";
        return $dsn;
    }

    public function connect($config = null, $times = 3)
    {
        $pdo = null;
        try {
            $dsn = $this->dsn($config);
            $pdo = new \PDO($dsn, $config['user'], $config['password'], [
                \PDO::ATTR_TIMEOUT => 60, // 超时时间
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // 异常处理模式
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // 关联数组检索
            ]);
        } catch (\PDOException $e) {
            throw new \PDOException(sprintf('Pdo Connect Error [%d]: %s', $e->getCode(), $e->getMessage()));
        }

        if (!is_object($pdo) && $times > 0) {
            return $this->connect($config, --$times);
        }

        return $pdo;
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
        $res = $this->query($sql, $bind);

        return $res;
    }

    public function query($sql = null, $bind = [])
    {
        if (empty($sql)) {
            throw new \Exception('');
        }

        try {
            $statement = $this->getDb()->query($sql);
        } catch (\PDOException $e) {
            if(strpos($e->getMessage(), 'server has gone away') !== false){
                $this->resetConnector();
                $statement = $this->getDb()->query($sql);
            }
        }

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
            throw new \Exception('Sql Empty!');
        }

        // 断线重连一次
        for ($i = 0; $i < 2; $i++) {
            try {
                $db = $this->getDb();
                // 返回 statement 对象或则 FALSE ，又或者抛出错误
                $statement = $db->prepare($sql);
                if ($statement === false) {
                    throw new \Exception('预处理失败，Errno: [%s], Error: [%s]', $db->errorCode(), $db->errorInfo());
                }

                foreach ($bind as $key => $bindValue) {
                    $statement->bindValue($key + 1, $bindValue, $this->selectType($bindValue));
                }

                if ($statement->execute() === false) {
                    throw new \Exception(sprintf('execute 执行失败，Errno: [%s], Error: [%s]',
                        $statement->errorCode(), $statement->errorInfo()));
                }

                break;
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'server has gone away') !== false && $i == 0) {
                    $this->resetConnector();
                } else {
                    throw new \Exception($e->getMessage());
                }
            }
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