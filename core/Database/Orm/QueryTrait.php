<?php

namespace Core\Database\Orm;

trait QueryTrait
{
    /**
     * SELECT * ...
     * @param string $columns
     * @return $this
     */
    public function select($columns = '')
    {
        $this->columns[] = $columns;
        return $this;
    }

    /**
     * 定义查询的表
     * @param string $table
     * @return $this
     */
    public function table($table = '')
    {
        $this->tables[] = $table;
        return $this;
    }

    /**
     * 定义查询的表
     * @param string $table
     * @return Builder
     */
    public function from($table = '')
    {
        return $this->table($table);
    }

    /**
     * JOIN 子句
     * @param string|JoinClause $table 要联表的表名，或者一个 JoinClause 实例
     * @param string $on
     * @param string $type 联表类型
     * @return $this
     * @throws \Exception
     */
    public function join($table, $on = '', $type = 'inner')
    {
        if ($table instanceof JoinClause) {
            $join = $table;
            // 可能传入的 JoinClause 类的 type 属性与参数 type 不一致，进行一次更新
            if ($type != 'inner') {// 如果是 inner，可能是默认值，不处理
                $join->type($type);
            }
        } else {
            $join = new JoinClause($table, $on, $type);
        }

        $this->joins[] = $join;

        return $this;
    }

    /**
     * inner 方式联表
     * @param string|JoinClause $table 要联表的表名，或者一个 JoinClause 实例
     * @param string $on
     * @return Builder
     * @throws \Exception
     */
    public function innerJoin($table, $on = '')
    {
        return $this->join($table, $on, 'inner');
    }

    /**
     * 左联进行联表
     * @param string|JoinClause $table 要联表的表名，或者一个 JoinClause 实例
     * @param string $on
     * @return Builder
     * @throws \Exception
     */
    public function leftJoin($table, $on = '')
    {
        return $this->join($table, $on, 'left');
    }

    /**
     * 右联进行联表
     * @param string|JoinClause $table 要联表的表名，或者一个 JoinClause 实例
     * @param string $on
     * @return Builder
     * @throws \Exception
     */
    public function rightJoin($table, $on = '')
    {
        return $this->join($table, $on, 'right');
    }

    /**
     * 拼接 where 子句
     * @param string|array|Closure $expression 表达式
     * @param null $operator
     * @param null $value
     * @param string $logic 逻辑运算符，使用 AND 或者 OR
     * @return $this
     * @throws \Exception
     */
    public function where($expression = '', $operator = null, $value = null, $logic = 'and')
    {
        if (!in_array(strtolower($logic), ['and', 'or'])) {
            throw new \Exception('未知的逻辑运算符');
        }
        $tmpWhere = [];

        if (!is_null($value)) {
            $tmpWhere = [
                'type' => 'base',
                'logic' => $logic,
                'field' => $expression,
                'operator' => $operator,
                'value' => $value,
            ];
        } else if (!is_null($operator)) {
            $tmpWhere = [
                'type' => 'base',
                'logic' => $logic,
                'field' => $expression,
                'operator' => '=',
                'value' => $operator,
            ];
        } else if (is_string($expression)) {
            $tmpWhere = [
                'type' => 'string',
                'logic' => $logic,
                'expression' => $expression,
            ];
        } else if (is_array($expression)) {
            $tmpWhere = [
                'type' => 'array',
                'logic' => $logic,
                'expression' => $expression,
            ];
        } else if ($expression instanceof Closure) {
            $newQuery = new Builder();
            $query = call_user_func_array($expression, [$newQuery]);

            $tmpWhere = [
                'type' => 'query',
                'logic' => $logic,
                'expression' => $query,
            ];
        }

        $this->wheres[] = $tmpWhere;

        return $this;
    }

    /**
     * 以 or 语句拼接 where 子句
     * @param string|array|Closure $expression 表达式
     * @param null $operator
     * @param null $value
     * @return Builder
     * @throws \Exception
     */
    public function orWhere($expression = '', $operator = null, $value = null)
    {
        return $this->where($expression, $operator, $value, 'OR');
    }

    /**
     * 拼接 IN 运算 where 子句
     * @param string $column
     * @param array|Closure $values
     * @param string $logic
     * @param bool $not
     * @return Builder
     * @throws \Exception
     */
    public function whereIn($column = '', $values = [], $logic = 'and', $not = false)
    {
        $typeName = $not ? 'NotIn' : 'In';
        return $this->whereSpecial($column, $values, $typeName, $logic);
    }

    /**
     * 以 OR 拼接 IN 运算 where 子句
     * @param string $column
     * @param array|Closure $values IN 的查询内容
     * @param bool $not
     * @return Builder
     * @throws \Exception
     */
    public function orWhereIn($column = '', $values = [], $not = false)
    {
        return $this->whereIn($column, $values, 'or', $not);
    }

    /**
     * 拼接 NOT IN 运算 where 子句
     * @param string $column
     * @param array|Closure $values NOT IN 的查询内容
     * @param string $logic
     * @return Builder
     * @throws \Exception
     */
    public function whereNotIn($column = '', $values = [], $logic = 'and')
    {
        return $this->whereIn($column, $values, $logic, true);
    }

    /**
     * 以 OR 拼接 NOT IN 运算 where 子句
     * @param string $column
     * @param array|Closure $values NOT IN 的查询内容
     * @return Builder
     * @throws \Exception
     */
    public function orWhereNotIn($column = '', $values = [])
    {
        return $this->whereIn($column, $values, 'or', true);
    }

    /**
     * 拼接 Between 运算 where 子句
     * @param string $column
     * @param array $values Between 的查询内容
     * @param string $logic
     * @param bool $not
     * @return Builder
     * @throws \Exception
     */
    public function whereBetween($column = '', $values = [], $logic = 'and', $not = false)
    {
        $typeName = $not ? 'NotBetween' : 'Between';
        return $this->whereSpecial($column, $values, $typeName, $logic);
    }

    /**
     * 以 OR 拼接 Between 运算 where 子句
     *
     * @param string $column
     * @param array|Closure $values Between 的查询内容
     * @return $this
     */

    /**
     * 以 OR 拼接 Between 运算 where 子句
     * @param string $column
     * @param array|Closure $values Between 的查询内容
     * @param bool $not
     * @return Builder
     * @throws \Exception
     */
    public function orWhereBetween($column = '', $values = [], $not = false)
    {
        return $this->whereBetween($column, $values, 'or', $not);
    }

    /**
     * 拼接 Not Between 运算 where 子句
     * @param string $column
     * @param array|Closure $values Not Between 的查询内容
     * @param string $logic
     * @return Builder
     * @throws \Exception
     */
    public function whereNotBetween($column = '', $values = [], $logic = 'and')
    {
        return $this->whereBetween($column, $values, $logic, true);
    }

    /**
     * 以 OR 拼接 Not Between 运算 where 子句
     * @param string $column
     * @param array|Closure $values Not Between 的查询内容
     * @return Builder
     * @throws \Exception
     */
    public function orWhereNotBetween($column = '', $values = [])
    {
        return $this->whereBetween($column, $values, 'or', true);
    }

    /**
     * 拼接 LIKE 运算 where 子句
     * @param string $column
     * @param string $value like 运算条件
     * @param string $logic
     * @param bool $not
     * @return Builder
     * @throws \Exception
     */
    public function whereLike($column = '', $value = '', $logic = 'and', $not = false)
    {
        $typeName = $not ? 'NotLike' : 'Like';
        return $this->whereSpecial($column, $value, $typeName, $logic);
    }

    /**
     * 以 OR 拼接 LIKE 运算 where 子句
     * @param string $column
     * @param string $value like 运算条件
     * @param bool $not
     * @return Builder
     * @throws \Exception
     */
    public function orWhereLike($column = '', $value = '', $not = false)
    {
        return $this->whereLike($column, $value, 'or', $not);
    }

    /**
     * 拼接 isnull 运算 where 子句
     * @param string $column
     * @param string $logic
     * @param bool $not
     * @return Builder
     * @throws \Exception
     */
    public function whereNull($column = '', $logic = 'and', $not = false)
    {
        $typeName = $not ? 'NotNull' : 'null';
        return $this->whereSpecial($column, null, $typeName, $logic);
    }

    /**
     * 以 OR 拼接 isnull 运算 where 子句
     * @param string $column
     * @return Builder
     * @throws \Exception
     */
    public function orWhereNull($column = '')
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * 拼接 Not Null 运算 where 子句
     * @param string $column
     * @param string $logic
     * @return Builder
     * @throws \Exception
     */
    public function whereNotNull($column = '', $logic = 'and')
    {
        return $this->whereNull($column, $logic, true);
    }

    /**
     * 以 OR 拼接 Not Null 运算 where 子句
     * @param string $column
     * @return Builder
     * @throws \Exception
     */
    public function orWhereNotNull($column = '')
    {
        return $this->whereNull($column, 'or', true);
    }

    /**
     * 拼接 Exists 运算 where 子句
     * @param $callback
     * @param string $logic
     * @param bool $not
     * @return $this
     */
    public function whereExists($callback, $logic = 'and', $not = false)
    {
        $typeName = $not ? 'NotExists' : 'Exists';

        if ($callback instanceof Builder) {
            $query = $callback;
        } else {
            $query = call_user_func($callback, new Builder());
        }


        $this->wheres[] = [
            'type' => $typeName,
            'logic' => $logic,
            'query' => $query
        ];

        return $this;
    }

    /**
     * 以 OR 拼接 Exists 运算 where 子句
     *
     * @param  Closure $callback
     * @param  bool     $not
     * @return Builder
     */
    public function orWhereExists(Closure $callback, $not = false)
    {
        return $this->whereExists($callback, 'or', $not);
    }

    /**
     * 特殊的 where 子句拼接
     * @param string $column
     * @param array $values
     * @param string $type
     * @param string $logic
     * @return $this
     * @throws \Exception
     */
    public function whereSpecial($column = '', $values = [], $type = '', $logic = 'and')
    {
        if (!in_array(strtolower($logic), ['and', 'or'])) {
            throw new \Exception('未知的逻辑运算符');
        }

        $typeName = strtolower($type);

        $allowTypeName = [
            'in', 'between', 'null', 'like',
            'notin', 'notbetween', 'notnull',
        ];

        if (!in_array($typeName, $allowTypeName)) {
            throw new \Exception('未知的条件运算符');
        }

        if ($values instanceof Closure) {
            $query = new Builder();
            $values = call_user_func_array($values, [$query]);
        }

        $this->wheres[] = [
            'type' => $typeName,
            'logic' => $logic,
            'column' => $column,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * 拼接 group by 子句
     *
     * @param string $group
     * @return $this
     */
    public function group($group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * 拼接 order by 子句
     *
     * @param string $order 排序的字段
     * @param string $type  排序类型
     * @return $this
     */
    public function order($order, $type = null)
    {
        if (!is_null($type) && in_array(strtolower($type), ['desc', 'asc'])) {
            $order = [
                $order,
                $type
            ];
        }

        $this->orders[] = $order;

        return $this;
    }

    /**
     * 拼接 having 子句
     *
     * @param mixed $having 条件
     * @param string $logic 逻辑运算符（AND | OR）
     * @return $this
     * @throws \Exception
     */
    public function having($having, $logic = 'and')
    {
        if (!in_array(strtolower($logic), ['and', 'or'])) {
            throw new \Exception('未知的逻辑运算符');
        }

        if (is_array($having)) {
            $this->havings = array_merge($this->havings, $having);
        } else {
            $this->havings[] = [
                'expression' => $having,
                'logic' => $logic
            ];
        }

        return $this;
    }

    /**
     * 设置 UNION 子句
     *
     * @param Builder|Closure $query
     * @param boolean $all
     * @return $this
     */
    public function union($query, $all = false)
    {
        if ($query instanceof Closure) {
            call_user_func($query, $query = new Builder());
        }

        $this->unions[] = compact('query', 'all');

        return $this;
    }

    /**
     * 拼接 LIMIT 子句
     *
     * @param mixed $limit
     * @return $this
     * @throws \Exception
     */
    public function limit($limit)
    {
        if (is_numeric($limit)) {
            $this->limit = $limit;
        } elseif (is_array($limit)) {
            list($offset, $take) = $limit;

            if (!is_numeric($offset) || !is_numeric($take)) {
                throw new \Exception('limit 子句参数错误！');
            }

            $this->limit = [$offset, $take];
        } else {
            throw new \Exception('limit 子句参数错误！');
        }

        return $this;
    }

    /**
     * 快捷的分页方法
     * @param int $current 当前页码
     * @param int $count   每页的条数
     * @return Builder
     * @throws \Exception
     */
    public function page($current = 1, $count = 20)
    {
        $start = ($current - 1) * $count;

        return $this->limit([$start, $count]);
    }
}