<?php

namespace Core;

class Loader
{
    /**
     * 自动加载函数列表
     */
    const AUTOlOAD_METHOD = [
        'sysAutoload'
    ];

    static public function init()
    {
        foreach (self::AUTOlOAD_METHOD as $method) {
            spl_autoload_register('self::' . $method);
        }
    }

    static public function sysAutoload(string $className)
    {
        $className = str_replace('\\', '/', $className);

        $filePath = ROOT_PATH . $className . '.php';
        if (!file_exists($filePath)) {
            throw new \Exception('Not Found class :' . $className . ' path :' . $filePath);
        }

        include_once $filePath;
    }
}