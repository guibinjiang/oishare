<?php
namespace Core\Tool;

/**
 * Tcp服务请求结果对象
 * @package Core\Client
 */
class TcpResponse
{
    public $code = self::ERR_NO_READY;
    public $msg = null;
    public $data = null;
    public $send;  //要发送的数据
    public $type;

    /**
     * 请求串号
     */
    public $requestId;

    /**
     * 回调函数
     * @var mixed
     */
    public $callback;

    /**
     * @var \Swoole\Client\TCP
     */
    public $socket = null;

    /**
     * SOA服务器的IP地址
     * @var string
     */
    public $server_host;

    /**
     * SOA服务器的端口
     * @var int
     */
    public $server_port;

    const ERR_NO_READY   = 8001; //未就绪
    const ERR_CONNECT    = 8002; //连接服务器失败
    const ERR_TIMEOUT    = 8003; //服务器端超时
    const ERR_SEND       = 8004; //发送失败

    const ERR_SERVER     = 8005; //server返回了错误码
    const ERR_UNPACK     = 8006; //解包失败了

    const ERR_HEADER     = 8007; //错误的协议头
    const ERR_TOOBIG     = 8008; //超过最大允许的长度
    const ERR_CLOSED     = 8009; //连接被关闭

    const ERR_RECV       = 8010; //等待接收数据

}
