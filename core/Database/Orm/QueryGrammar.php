<?php

namespace Core\Database\Orm;

use Closure;

class QueryGrammar
{

    /**
     * 需要绑定到 prepare 的信息
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * 解析 SELECT 语句
     * @param Builder $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {

        $original = $query->columns;

        // 查询字段为空则取默认 * , select * ...
        if (empty($query->columns)) {
            $query->columns = ['*'];
        }

        // 清空绑定
        $this->bindings = [];

        $sqls = $this->compileComponents($query);

        $query->columns = $original;

        return implode(' ', $sqls);
    }

    protected function compileComponents(Builder $query)
    {
        $selectComponents = [
            'aggregate',
            'columns',
            'tables',
            'joins',
            'wheres',
            'groups',
            'havings',
            'orders',
            'limit',
            'offset',
            'unions',
            'lock',
        ];
        $sql = [];

        foreach ($selectComponents as $component) {
            if (!empty($query->$component)) {
                $method = 'compile' . ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    /**
     * 解析聚合函数查询的 SELECT 子句
     * @param Builder $query
     * @param $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->wrap($aggregate['columns']);

        return 'SELECT '. strtoupper($aggregate['function']) .'('. $column .') as aggregate';
    }

    /**
     * 解析查询字段
     * @param Builder $query
     * @param $columns
     * @return bool|string
     */
    protected function compileColumns(Builder $query, $columns)
    {
        // 如果调用了统计方法，则不处理 select 子句
        if (!empty($query->aggregate)) {
            return false;
        }

        $columnSql = $this->wrap($columns);

        return 'SELECT ' . $columnSql;
    }

    public function compileWheres(Builder $query)
    {
        $whereSql = '';
        foreach ($query->wheres as $whereItem) {
            $type = strtolower($whereItem['type']);
            $not = false;
            if (strpos($type, 'not') === 0) {
                $type = substr($type, 3);
                $not = true;
            }
            $methodName = 'where' . ucfirst($type);
            if (!empty($whereSql)) {
                $whereSql .= ' ' . strtoupper($whereItem['logic']);
            }
            $whereSql .= ' ' . $this->$methodName($whereItem, $not);
        }

        return ($query instanceof JoinClause ? 'ON' : 'WHERE') . $whereSql;
    }

    /**
     * 解析查询时 from 子句
     * @param Builder $query
     * @return string
     */
    public function compileTables(Builder $query)
    {
        // 表名运行 wrap，包装 ` 引号
        return 'FROM ' . $this->wrapTable($query, $query->tables);
    }

    /**
     * 解析联表子句
     * @param Builder $query
     * @param $joins
     * @return bool|string
     */
    public function compileJoins(Builder $query, $joins)
    {
        $joinSql = '';
        foreach ($joins as $item) {
            $joinSql .= ' ' . $this->compileJoin($item);
        }

        // 去除左边的空格
        return substr($joinSql, 1);
    }

    /**
     * 处理每一个 join
     * @param JoinClause $join
     * @return string
     */
    protected function compileJoin(JoinClause $join)
    {
        $table = $this->wrapTable($join, $join->tables, true);
        return strtoupper($join->type) . ' JOIN ' . $table .' '. $this->compileWheres($join);
    }

    public function compileGroups(Builder $query)
    {
        return 'GROUP BY ' . $this->wrap($query->groups);
    }

    /**
     * 编译 order by 子句
     * @param Builder $query
     * @param $orders
     * @return string
     */
    public function compileOrders(Builder $query, $orders)
    {
        return 'ORDER BY ' . implode(',', array_map(function($order) {
                if (is_array($order)) {// 数组格式的 order，第一个为字段，第二个为排序方式
                    list($column, $sort) = $order;
                } elseif (strpos(trim($order), ' ') !== false) {// 空格隔开，分割成数组
                    // 可能空格有多个，先剔除前后的空格，并分割
                    $orderItems = explode(' ', trim($order));
                    $column = $orderItems[0];   // 分割后第一个项是字段名
                    $sort = '';
                    unset($orderItems[0]);
                    // 剩下的，取第一个非空项为排序方式
                    foreach ($orderItems as $item) {
                        if ($item) {
                            $sort = $item;
                            break;
                        }
                    }
                } else {
                    $column = $order;
                    $sort = '';
                }

                $sort = strtoupper(trim($sort));
                if (!in_array($sort, ['ASC', 'DESC'])) {
                    $sort = '';
                }

                return $this->wrap($column) . (!empty($sort) ? ' ' . $sort : '');
            }, $orders));
    }

    public function compileLimit(Builder $query, $limit)
    {
        if (is_array($limit)) {
            list($offset, $take) = $limit;
        } elseif (is_numeric($limit)) {
            $offset = 0;
            $take = $limit;
        } else {
            return '';
        }

        if (!is_numeric($offset) || !is_numeric($take)) {
            return '';
        }

        return 'LIMIT ' . $offset . ',' . $take;
    }

    public function compileUnions(Builder $query)
    {
        $unionSql = '';
        foreach ($query->unions as $item) {
            $command = ' UNION ' . ($item['all'] ? 'ALL ' : '');
            $unionSql .= $command . $this->compileSelect($item['query']);
        }

        return $unionSql;
    }

    protected function compileHaving(Builder $query, $havings)
    {
        $havingSql = '';
        foreach ($havings as $item) {
            if (is_array($item)) {
                $expression = $item['expression'];
                $logic = $item['logic'];
            } else {
                $expression = $item;
                $logic = 'AND';
            }

            if (!empty($havingSql)) {
                $havingSql .= ' ' . $logic;
            }

            $havingSql .= ' ' . $expression;
        }

        return 'HAVING' . $havingSql;
    }

    public function compileLock(Builder $query, $value)
    {
        if (!is_string($value)) {
            return $value ? 'for update' : 'lock in share mode';
        }

        return $value;
    }

    public function compileUpdate(Builder $query, $maps)
    {
        $table = $this->wrapTable($query, $query->tables, true);

        $this->bindings = [];
        $columns = '';
        foreach ($maps as $key => $value) {
            $columns .= $this->wrap($key) . ' = ?,';
            $this->bindings[] = $value;
        }

        $columns = substr($columns, 0, -1);

        $joins = '';

        if (isset($query->joins)) {
            $joins = ' ' . $this->compileJoins($query, $query->joins);
        }

        if (empty($query->wheres)) {
            throw new OrmException('更新操作必须有 where 子句');
        }

        $wheres = $this->compileWheres($query);

        return trim("UPDATE {$table}{$joins} SET $columns $wheres");
    }

    public function compileInsert(Builder $query, $maps, $type = 'insert')
    {
        $table = $this->wrapTable($query, $query->tables, true);

        $this->bindings = [];
        if (!is_array(reset($maps))) {
            $maps = [$maps];
        }

        $columnsArr = array_keys(reset($maps));
        $columnsCount = count($columnsArr);
        $columns = $this->wrap($columnsArr);

        $values = [];
        foreach ($maps as $item) {
            $this->bindings = array_merge($this->bindings, array_values($item));
            $values[] = '(' . implode(',', array_fill(0, $columnsCount, '?')) . ')';
        }

        $parameters = implode(',', $values);

        $command = '';

        switch (strtolower($type)) {
            case 'ignore':
                $command .= 'INSERT IGNORE';
                break;

            case 'replace':
                $command .= 'REPLACE INTO';
                break;

            default :
                $command .= 'INSERT INTO';
        }

        return "$command $table ({$columns}) VALUES {$parameters}";
    }

    public function compileDelete(Builder $query)
    {
        $table = $this->wrapTable($query, $query->tables, true);

        if (empty($query->wheres)) {
            throw new OrmException('删除作必须有 where 子句');
        }

        $wheres = $this->compileWheres($query);

        return "DELETE FROM {$table} {$wheres}";
    }

    /**
     * 返回语法解析生成的 binding 数据
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    public function resetBindings()
    {
        $this->bindings = [];
        return $this;
    }

    /**
     * 对子句中涉及的字段内容包装“`”引号
     *
     * @param mixed $column
     * @return string
     */
    protected function wrap($column)
    {
        // 数组，对子项运行一次包装，并用逗号合并返回
        if (is_array($column)) {
            return implode(',', array_map([$this, 'wrap'], $column));
        }

        if (is_callable($column)) {
            return call_user_func($column);
        }

        // 消除前后空格进行处理
        $column = trim($column);
        // 星号不处理
        if ($column == '*') {
            return $column;
        }

        // 有逗号，切割为数组处理一遍
        if (strpos($column, ',') !== false) {
            $columnsArr = explode(',', $column);
            return implode(',', array_map([$this, 'wrap'], $columnsArr));
        }

        // 有别名，别名和字段名分别包装引号
        if (stripos($column, ' as ')) {
            $column = str_ireplace(' AS ', ' as ', $column);
            list($columnName, $alias) = explode(' as ', $column);

            return $this->wrap($columnName) . ' AS ' . $this->wrap($alias);
        }

        // 用点选择表
        if (strpos($column, '.') !== false) {
            list($tableName, $columnName) = explode('.', $column);

            return $this->wrap($tableName) . '.' . $this->wrap($columnName);
        }

        // 已经加过引号，不处理直接返回
        // 如果内容包含空格和括号，可能是函数调用，也不进行处理
        if (strpos($column, '`') !== false
            || strpos($column, '\'') !== false
            || strpos($column, '"') !== false
            || strpos($column, ' ') !== false
            || strpos($column, '(') !== false
            || strpos($column, ')') !== false) {
            return $column;
        }

        return '`' . $column . '`';
    }

    /**
     * 为表名包装生成表名的结果集
     *
     * @param Builder $query
     * @param array|string $tables 要格式化的表名
     * @param bool $justOne 是否只是用一个表名，如果是数组，或含有逗号，只返回一条
     * @return mixed|string
     */
    protected function wrapTable(Builder $query, $tables, $justOne = false)
    {
        if (!is_array($tables)) {
            if (strpos($tables, ',') === false) {
                $tables = [$tables];
            } else {
                $tables = explode(',', $tables);
            }
        }

        if ($justOne) {
            $tables = [reset($tables)];
        }

        // 如果是回调函数，直接返回回调函数的内容
        if ($tables instanceof Closure) {
            return call_user_func($tables);
        }

        return implode(',', array_map(function($table) use ($query, $justOne) {
            return $this->wrapOneTable($query, $table, $justOne);
        }, $tables));
    }

    /**
     * 将一个表名进行包装
     * 将进行如下操作：
     * 根据配置，添加表前缀
     * 为表添加 ` 引号
     *
     * @param Builder $query
     * @param $table
     * @param bool $justOne
     * @return mixed|string
     */
    protected function wrapOneTable(Builder $query, $table, $justOne = false)
    {
        if (strpos($table, ',') !== false) {
            return $this->wrapTable($query, $table, $justOne);
        }

        $table = trim($table);

        if (strpos($table, '.') !== false) {
            list($databaseName, $tableName) = explode('.', $table);

            return $this->wrap($databaseName) . '.' . $this->wrapOneTable($query, $tableName);
        }

        if (stripos($table, ' as ')) {
            // 转换大小写
            $table = str_ireplace(' AS ', ' as ', $table);
            list($tableName, $alias) = explode(' as ', $table);

            return $this->wrapOneTable($query, $tableName) . ' AS ' . $this->wrap($alias);
        }


        // 已经加过引号，不处理直接返回
        if (strpos($table, '`') !== false
            || strpos($table, '\'') !== false
            || strpos($table, '"') !== false
            || strpos($table, ' ') !== false
            || strpos($table, '(') !== false
            || strpos($table, ')') !== false) {
            return $table;
        }

        return '`'. $this->wrapTablePre($query, $table) .'`';
    }

    /**
     * 为表名添加前缀
     * @param Builder $query
     * @param $table
     * @return string
     */
    protected function wrapTablePre(Builder $query, $table)
    {
        return $query->getTablePrefix() . $table;
    }

    protected function wrapValue($value)
    {
        return '\'' . $value . '\'';
    }

    protected function whereBase($options)
    {
        $this->bindings[] = $options['value'];
        return '(' . $this->wrap($options['field']) .' '. $options['operator'] . ' ?)';
    }

    protected function whereString($options)
    {
        return '(' . $options['expression'] . ')';
    }

    protected function whereArray($options)
    {
        // $options 是索引数组，直接用 AND 将其连接
        // 这里需要用全等，以免只有一个的出现 string == 0 的情况
        if (array_keys($options['expression']) === range(0, count($options['expression']) - 1)) {
            return '(' . implode(' AND ', $options['expression']) . ')';
        }

        $expressions = [];
        foreach ($options['expression'] as $column => $value) {
            $expressions[] = $this->wrap($column) . '=?';
            $this->bindings[] = $value;
        }

        return '(' . implode(' AND ', $expressions) . ')';
    }

    protected function whereQuery($options)
    {
        if (!$options['expression'] instanceof Builder) {
            return '';
        }

        $shift = $options['expression'] instanceof JoinClause ? 3 : 6;

        return '(' . substr($this->compileWheres($options['expression']), $shift) . ')';
    }

    protected function whereIn($options, $not = false)
    {
        if ($options['values'] instanceof Builder) {
            $valuesSql = $this->compileSelect($options['values']);
        } elseif (is_array($options['values'])) {

            $valuesCount = count($options['values']);
            $valuesSql = implode(',', array_fill(0, $valuesCount, '?'));

            $this->bindings = array_merge($this->bindings, $options['values']);
        } else {
            return '';
        }

        $command = $not ? 'NOT IN' : 'IN';
        return $this->wrap($options['column']) . " $command ($valuesSql)";
    }

    protected function whereBetween($options, $not = false)
    {
        $command = $not ? 'NOT BETWEEN' : 'BETWEEN';
        $upper = $options['values'][0];
        $lower = $options['values'][1];
        $this->bindings[] = $upper;
        $this->bindings[] = $lower;
        return $this->wrap($options['column'])
            . " $command ? AND ?";
    }

    protected function whereNull($options, $not = false)
    {
        $command = 'IS ' . ($not ? 'NOT ' : '') . 'NULL';

        return $this->wrap($options['column']) . $command;
    }

    protected function whereExists($options, $not = false)
    {
        $valuesSql = $this->compileSelect($options['query']);
        $command = ($not ? 'NOT ' : '') . 'EXISTS';

        return $command . " ($valuesSql)";
    }

}