<?php
namespace Core\Database\Orm;

use Exception;

class JoinClause extends Builder
{

    /**
     * 联表类型，默认为 inner
     *
     * @var string
     */
    public $type = 'inner';

    /**
     * JoinClause constructor.
     * @param $table
     * @param string $on
     * @param string $type
     * @throws Exception
     */
    public function __construct($table, $on = '', $type = 'inner')
    {
        // 定义查询的表
        $this->table($table);

        // 设置联表类型
        $this->type($type);

        if ($on) {
            $this->on($on);
        }
    }

    /**
     * 联表条件，直接调用 where 方法
     * @param string $expression
     * @param null $operator
     * @param null $value
     * @param string $logic
     * @return JoinClause
     * @throws Exception
     */
    public function on($expression = '', $operator = null, $value = null, $logic = 'and')
    {
        return $this->where($expression, $operator, $value, $logic);
    }

    /**
     * 设置联表类型
     * @param string $type
     * @return $this
     * @throws Exception
     */
    public function type($type = 'inner')
    {
        if (!empty($type) && !in_array(strtolower($type), ['inner', 'left', 'right'])) {
            throw new Exception('联表类型错误');
        }
        $this->type = $type;
        return $this;
    }
}