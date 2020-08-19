<?php

namespace Core\Database\Orm;

use Closure;
use Core\Factory\Factory;
use Exception;
use Core\Database\Connector\Connector;

class Builder
{
    use QueryTrait;

    /**
     * 聚合
     * @var null
     */
    public $aggregate = null;

    /**
     * 列字段
     * @var array
     */
    public $columns = [];

    /**
     * 表名
     * @var array
     */
    public $tables = [];

    /**
     * 连接
     * @var array
     */
    public $joins = [];

    /**
     * 条件
     * @var array
     */
    public $wheres = [];

    /**
     * 分组
     * @var array
     */
    public $groups = [];

    /**
     * 分组条件
     * @var array
     */
    public $havings = [];

    /**
     * 排序
     * @var array
     */
    public $orders = [];

    /**
     * 页码限制
     * @var array
     */
    public $limit = [];

    /**
     * 偏移量
     * @var int
     */
    public $offset = 0;

    /**
     * 联合
     * @var null
     */
    public $unions = null;

    /**
     * 锁
     * @var null
     */
    public $lock = null;

    /**
     * 语法解析对象
     * @var QueryGrammar
     */
    public $queryGrammar = null;

    /**
     * 是否强制使用主库
     * @var bool
     */
    public $useMaster = false;

    /**
     * 连接实例
     * @var null
     */
    public $connector = null;

    /**
     * SQL语句
     * @var array
     */
    public $sqlStack = [];

    /**
     * Builder constructor.
     */
    public function __construct()
    {
        $this->queryGrammar = $this->makequeryGrammar();
    }

    /**
     * 获取一个语法解析器实例
     * @return queryGrammar
     */
    public function makeQueryGrammar()
    {
        return new queryGrammar();
    }

    /**
     * 遇到未知的方法请求，抛出 \Exception
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        throw new Exception('Unkown method :' . $name);
    }

    /**
     * 强制使用主库查询
     * @return $this
     */
    public function useMaster()
    {
        $this->useMaster = true;
        return $this;
    }

    /**
     * 开启事务
     * @return $this
     */
    public function begin()
    {
        return $this;
    }

    /**
     * 提交事务
     * @return $this
     */
    public function commit()
    {
        return $this;
    }

    /**
     * 回滚事务
     * @return $this
     */
    public function rollback()
    {
        return $this;
    }

    /**
     * 回调的方式启用事务
     * @param Closure $transactionFunction
     * @param null $rollbackCallback
     */
    public function transaction(Closure $transactionFunction, $rollbackCallback = null)
    {
        $this->begin();

        try {
            call_user_func_array($transactionFunction, [(new Builder())]);

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();

            // 如果传入了回滚的回调函数，调用
            if (is_callable($rollbackCallback)) {
                call_user_func_array($rollbackCallback, [$e]);
            }
        }
    }

    /**
     * 设置锁
     *
     * @param Boolean|string $value 锁的类型
     * @return $this
     */
    public function lock($value = true)
    {
        $this->lock = $value;

        if (!is_null($this->lock)) {
            $this->useMaster();
        }

        return $this;
    }

    /**
     * 设更新锁
     *
     * @return $this
     */
    public function lockForUpdate()
    {
        return $this->lock(true);
    }

    /**
     * 设置排它锁
     *
     * @return $this
     */
    public function sharedLock()
    {
        return $this->lock(false);
    }

    /**
     * 返回一个 count 统计结果
     * @param string $columns
     * @return int
     * @throws Exception
     */
    public function count($columns = '*')
    {
        return (int) $this->aggregate(__FUNCTION__, $columns);
    }

    /**
     * 返回一个 min 统计结果
     * @param $column
     * @return bool
     * @throws Exception
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 返回一个 max 统计结果
     * @param $column
     * @return bool
     * @throws Exception
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 返回一个 sum 计算结果
     * @param $column
     * @return int
     * @throws Exception
     */
    public function sum($column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * 返回一个 avg 统计结果
     * @param $column
     * @return bool
     * @throws Exception
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 执行统计查询，并返回结果
     * @param $function
     * @param array $columns
     * @return bool
     * @throws Exception
     */
    public function aggregate($function, $columns = ['*'])
    {
        $result = $this->setAggregate($function, $columns)->getRow();

        if ($result && isset($result['aggregate'])) {
            return $result['aggregate'];
        } else {
            return false;
        }
    }

    /**
     * 设置聚合搜索
     * @param $function
     * @param array $columns
     * @return $this
     */
    public function setAggregate($function, $columns = ['*'])
    {
        $this->aggregate = compact('function', 'columns');

        if (empty($this->groups)) {
            $this->orders = null;
        }

        return $this;
    }

    /**
     * 获取所有数据行返回
     * @return mixed
     */
    public function getAll()
    {
        $sql = $this->queryGrammar->compileSelect($this);
        $this->sqlStack($sql)->reset();
        $result = $this->getConnector()->useMaster($this->useMaster)->prepare($sql, $this->queryGrammar->getBindings())->fetchAll();
        return $result;
    }

    /**
     * 获取第一行数据
     * @return mixed
     * @throws Exception
     */
    public function getRow()
    {
        $results = $this->limit(1)->getAll();
        return reset($results);
    }

    /**
     * 打印SQL语句
     * @return string
     */
    public function toSql()
    {
        $sql = end($this->sqlStack);
        return (string) $sql;
    }

    public function __toString()
    {
        return (string) $this->toSql();
    }

    /**
     * 更新操作
     * 使用 where 方法设定 where 子句
     * 未设置 where 子句，将抛出 OperationWithoutWhereException 异常
     *
     * @param array $maps 更新数据的键值对
     * @return int 执行成功，返回影响的条数
     */
    public function update($maps = [])
    {
        $sql = $this->queryGrammar->compileUpdate($this, $maps);
        $this->sqlStack($sql)->reset();
        return $this->getConnector()->useMaster($this->useMaster()->useMaster)->prepare($sql, $this->queryGrammar->getBindings())->affectedRows();

        //$connector = ConnectorFactory::getConnector($this->connectionName);
        //return $connector->prepareWithAffectingStatement($sql, $this->queryGrammar->getBindings());
    }

    /**
     * 插入记录，支持批量插入
     *
     * @param array $maps 要插入的数据键值对，可用批量
     * @param string $type 写入方式，有如下值：insert|ignore|replace
     * @return mixed 写入成功，返回 id
     */
    public function insert($maps = [], $type = 'insert')
    {
        $sql = $this->queryGrammar->compileInsert($this, $maps, $type);
        $this->getConnector()->useMaster($this->useMaster()->useMaster)->prepare($sql, $this->queryGrammar->getBindings());
        $this->sqlStack($sql)->reset();
        $this->queryGrammar->resetBindings();
        $lastInsertId=$this->getConnector()->lastInsertId();
        return $lastInsertId;
    }

    /**
     * 删除记录
     * 使用 where 方法设定 where 子句
     * 未设置 where 子句，将抛出 OperationWithoutWhereException 异常
     *
     * @return int 执行成功，返回影响的条数
     */
    public function delete()
    {
        $sql = $this->queryGrammar->compileDelete($this);
        $this->sqlStack($sql)->reset();
        return $this->getConnector()->useMaster($this->useMaster()->useMaster)->prepare($sql, $this->queryGrammar->getBindings())->affectedRows();

        //$connector = ConnectorFactory::getConnector($this->connectionName);
        //return $connector->prepareWithAffectingStatement($sql, $this->queryGrammar->getBindings());
    }

    /**
     * 获取一个字段值
     * 从 SQL 中读取第一个字段的值返回
     *
     * @return string
     */
    public function getOne()
    {
        $row = $this->getRow();
        return !empty($row) ? reset($row) : null;
    }

    /**
     * 获取指定字段值
     *
     * @param string $field
     * @return string
     */
    public function value($field = '')
    {
        $this->select($field);
        $row = $this->getRow();

        return isset($row[$field]) ? $row[$field] : null;
    }

    /**
     * 获取该连接配置的表前缀
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->getConnector()->getPrefix();
    }

    /**
     * 重置查询构建器
     * @return $this
     */
    public function reset()
    {
        $this->aggregate = null;
        $this->columns = [];
        $this->tables = [];
        $this->joins = [];
        $this->wheres = [];
        $this->groups = [];
        $this->havings = [];
        $this->orders = [];
        $this->limit = [];
        $this->offset = 0;
        $this->unions = null;
        $this->lock = null;
        $this->useMaster = false;

        return $this;
    }

    /**
     * 设置连接实例
     * @param null $connector
     * @return $this
     * @throws \Exception
     */
    public function connector($connector = null)
    {
        if (is_null($connector)) {
            $connector = Factory::mysql();
        }

        if (!($connector instanceof Connector)) {
            throw new \Exception('缺少 MySql 连接实例！');
        }

        $this->connector = $connector;

        return $this;
    }

    public function getConnector()
    {
        if (!($this->connector instanceof Connector)) {
            $this->connector();
        }

        return $this->connector;
    }

    /**
     * 记录 SQL 语句
     * @param string $sql
     * @return $this
     */
    protected function sqlStack(string $sql)
    {
        if (!empty($sql)) {
            $this->sqlStack[] = $sql;
        }

        return $this;
    }

    // 获取所有SQL语句
    public function allSql()
    {
        return $this->sqlStack;
    }

}