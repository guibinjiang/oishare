<?php

namespace Core\Base;

class CoSingle
{
    protected static $_instance = [];

    static public function getInstance($className, ...$args)
    {
        $cid = CoManager::getInstance()->getCid();

        $className = trim(str_replace('/', '\\', $className), '\\');

        if (!isset(static::$_instance[$cid][$className])) {
            static::$_instance[$cid][$className] = new $className(...$args);
        }

        return static::$_instance[$cid][$className];
    }

    static public function removeInstance()
    {
        $cidArr = CoManager::getInstance()->getCurrentCid();

        foreach ($cidArr as $cid) {
            if (isset(static::$_instance[$cid])) {
                unset(static::$_instance[$cid]);
            }
        }

        CoManager::getInstance()->removeCurrentCidMap($cidArr);
    }
}