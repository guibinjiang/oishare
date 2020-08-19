<?php
namespace Core\Swoole\Route;

use Core\Tool\TcpClient;

class TcpRequestProxy
{
    public $get;

    public $post;

    public $request;

    public $server;

    public $header;

    public $cookie;

    public function __construct($params = '')
    {
        $params = TcpClient::decode($params, TcpClient::DECODE_JSON);
        $this->get = $params;
        $this->post = $params;
        $this->request = $params;
        $this->server = [
            'request_method' => isset($params['env']['request_method']) ? $params['env']['request_method'] : 'GET',
            'REQUEST_METHOD' => isset($params['env']['request_method']) ? $params['env']['request_method'] : 'GET',
            'request_uri' => '',
            'REQUEST_URI' => '',
            'path_info' => '',
            'PATH_INFO' => '',
            'remote_addr' => '',
            'REMOTE_ADDR' => '',
            'http_host' => '',
            'HTTP_HOST' => '',
        ];
    }
}