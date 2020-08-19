<?php

namespace Core\Database\Orm;

use Core\Base\CoSingle;

class DB
{
    public static function __callStatic($method, $arguments)
    {
        $query = CoSingle::getInstance(Builder::class);

        return call_user_func_array([$query, $method], $arguments);
    }
}