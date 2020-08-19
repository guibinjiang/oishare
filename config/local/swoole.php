<?php

return [
    'http' => [
        'host'             => '192.168.33.100', // ip地址
        'port'             => 9501,             // 端口
        'process_name'     => 'OIServer',       // swoole 进程名称
        'worker_num'       => 1,                // 一般设置为服务器CPU数的1-4倍
        'task_worker_num'  => 1,                // task进程的数量
        'task_ipc_mode'    => 3,                // 使用消息队列通信，并设置为争抢模式
        'task_max_request' => 10000,            // task进程的最大任务数
        'max_request'      => 10000,            //进程最大处理数
        'reload_async'     => true,             //异安全重启
        'dispatch_mode'    => 1,                // 1轮循模式
        'pid_file'         => LOG_PATH  . 'Swoole' . DIRECTORY_SEPARATOR . 'Pid' . DIRECTORY_SEPARATOR . 'swoole' . '.pid',  //日志
    ],

    'tcp' => [
        'host'                  => '0.0.0.0',           //ip地址
        'port'                  => 9502,                //端口
        'process_name'          => 'OIServerTCP',       //swoole 进程名称
        'worker_num'            => 1,                   //一般设置为服务器CPU数的1-4倍
        'task_worker_num'       => 1,                   //task进程的数量
        'task_ipc_mode'         => 3,                   //使用消息队列通信，并设置为争抢模式
        'task_max_request'      => 10000,               //task进程的最大任务数
        'task_enable_coroutine' => true,                //task进程开启使用异步和协程
        'daemonize'             => 0,                   //以守护进程执行
        'max_request'           => 10000,               //进程最大处理数
        'reload_async'          => true,                //异步安全重启
        'dispatch_mode'         => 1,                   //1轮循模式
        'open_eof_split'        => true,                //EOF自动分包
        'package_eof'           => "\r\n",              //EOF字符串
        'buffer_output_size'    => 2*1024*1024,         //单次最大发送的数据2M
        'pid_file'              => LOG_PATH  . 'Swoole' . DIRECTORY_SEPARATOR . 'Pid' . DIRECTORY_SEPARATOR . 'swooleRPC' . '.pid',  //日志
        'coroutine_client'      => true,                //使用协程mysql、redis
        'remote_shell'          => false,               //连接远程调试器
    ]
];