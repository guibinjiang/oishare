<?php

use Core\Base\CoSingle;

if (!function_exists('_class')) {
    /**
     * @param $class
     * @param mixed ...$args
     * @return Core\BaseObject
     */
    function _class($class, ...$args) {
        return CoSingle::getInstance($class, ...$args);
    }
}

if (!function_exists('_logic')) {
    /**
     * @param $class
     * @param mixed ...$args
     * @return Core\Base\Logic
     */
    function _logic($class, ...$args) {
        return CoSingle::getInstance($class, ...$args);
    }
}

if (!function_exists('_model')) {
    /**
     * @param $class
     * @param mixed ...$args
     * @return Core\Base\model
     */
    function _model($class, ...$args) {
        return CoSingle::getInstance($class, ...$args);
    }
}