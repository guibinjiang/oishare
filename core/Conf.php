<?php

namespace Core;

class Conf
{
    private static $filePath = CONF_PATH . DEV_ENV . DIRECTORY_SEPARATOR;

    private static $conf = [];

    /**
     * 获取指定配置数据
     * @param $name
     * @param null $key
     * @return mixed|string
     */
    public static function get($name, $key = null)
    {
        // 初始化配置数据
        self::init($name);

        // 获取指定字段数据
        if (isset($key)) {
            if (stripos($key, '.') !== false) {
                $config = self::$conf[$name];
                foreach ($key as $k){
                    if (isset($config[$k])){
                        $config = $config[$k];
                    } else {
                        $config = '';
                    }
                }
                return $config;
            } else {
                return isset(self::$conf[$name][$key]) ? self::$conf[$name][$key] : '';
            }
        } else {
            return self::$conf[$name];
        }
    }

    /**
     * 初始化配置数据
     * @param $name
     * @return bool
     */
    public static function init($name)
    {
        if (!isset(self::$conf[$name])) {
            $confPath = self::$filePath . $name . '.php';
            if (!file_exists($confPath)) {
                return false;
            }
            self::$conf[$name] = include($confPath);
        }
    }

}