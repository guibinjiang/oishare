<?php

namespace Core\Traits;

Trait SingleTrait
{
    public static $instance = null;

    static public function getInstance(...$args)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static(...$args);

        }
        return self::$instance;
    }
}

