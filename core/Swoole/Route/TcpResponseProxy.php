<?php

namespace Core\Swoole\Route;

class TcpResponseProxy
{
    public $code = null;

    public $header = [];

    /** @var \Swoole\Server */
    public $server = null;

    public $fd = null;

    public $error = null;

    public function __construct($server, $fd)
    {
        $this->server = $server;
        $this->fd = $fd;
    }

    public function status($code)
    {
        $this->code = $code;
    }

    public function header($key, $value)
    {
        $this->header[$key] = $value;
    }

    public function gzip()
    {
    }

    public function end($data)
    {
        $this->server->send($this->fd, $data);
    }

    public function cookie($key, $value, $expire, $path, $domain, $secure, $httponly)
    {
    }

    public function push($data)
    {
        $this->server->send($this->fd, $data);
    }

    public function pushAll($data)
    {
        $this->server->send($this->fd, $data);
    }

    public function send($data)
    {
        $this->server->send($this->fd, $data);
    }

    public function close()
    {
        $this->server->close($this->fd);
    }

    /**
     * 返回错误信息
     *
     * @return string
     */
    public function error()
    {
        return $this->error;
    }
}