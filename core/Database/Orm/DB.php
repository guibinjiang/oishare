<?php

namespace Core\Database\Orm;

class DB
{
    public static function __callStatic($method, $arguments)
    {
        $query = _class(Builder::class);

        return call_user_func_array([$query, $method], $arguments);
    }
}