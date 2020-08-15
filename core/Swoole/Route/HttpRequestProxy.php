<?php
namespace Core\Swoole\Route;

use Core\BaseObject;

class HttpRequestProxy
{
    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->request = $_REQUEST;
        $this->server = $_SERVER;
        $this->header = [];
        $this->cookie = $_COOKIE;
    }
}