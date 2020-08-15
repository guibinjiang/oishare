<?php

return [
    'http' => [
        'host'             => '192.168.33.100', // ip地址
        'port'             => 9501,             // 端口
        'process_name'     => 'BGService',      // swoole 进程名称
        'worker_num'       => 1,                // 一般设置为服务器CPU数的1-4倍
        'task_worker_num'  => 1,                // task进程的数量
        'task_ipc_mode'    => 3,                // 使用消息队列通信，并设置为争抢模式
        'task_max_request' => 10000,            // task进程的最大任务数
        'max_request'      => 10000,            //进程最大处理数
        'reload_async'     => true,             //异安全重启
        'dispatch_mode'    => 1,                // 1轮循模式
        'log_file'         => LOG_PATH  . 'Swoole' . DIRECTORY_SEPARATOR . 'Swoole' . date('Ymd') . '.log',  //日志
        'pid_file'         => LOG_PATH  . 'Swoole' . DIRECTORY_SEPARATOR . 'Pid' . DIRECTORY_SEPARATOR . 'swoole' . '.pid',  //日志
    ],
];