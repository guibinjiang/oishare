<?php
/**
 * Created by PhpStorm.
 * User: zhushijie
 * Date: 19-4-18
 * Time: 下午4:34
 */

namespace Core;


class Register
{
    /**
     * 对象列表
     * @var array
     */
    protected $objects = [];

    /**
     * 注册对象
     * @param $alias
     * @param $object
     */
    public function set($alias, $object)
    {
        $this->objects[$alias] = $object;
    }

    /**
     * 获取对象
     * @param $alias
     * @return mixed
     */
    public function get($alias)
    {
        return isset($this->objects[$alias]) ?: null;
    }

    /**
     * 销毁对象
     * @param $alias
     */
    public function _unset($alias)
    {
        unset($this->objects[$alias]);
    }

    /**
     * 查看所有对象
     * @return array
     */
    public function all()
    {
        return $this->objects;
    }
}