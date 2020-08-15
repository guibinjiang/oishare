<?php
namespace Core\Swoole\Route;

use Core\BaseObject;

class RequestManager extends BaseObject
{
    /**
     * @var \Swoole\Http\request
     */
    public $request;

    public function __construct($request = null)
    {
        parent::__construct();

        $this->initRequest($request);
    }

    private function initRequest($request = null)
    {
        $this->request = $request;
        $requestUri = $this->getRequestUri();
        if ($requestUri && !strpos($requestUri, 'index.php')) {
            list($app, $controller, $action) = explode('/', trim($requestUri, '/'));
            if (!empty($app)) {
                $this->request->get['app'] = $app;
            }
            if (!empty($controller)) {
                $this->request->get['controller'] = $controller;
            }
            if (!empty($action)) {
                $this->request->get['action'] = $action;
            }
        }
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request->get;
        } else {
            return isset($this->request->get[$key]) ? $this->request->get[$key] : $default;
        }
    }

    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request->post;
        } else {
            return isset($this->request->post[$key]) ? $this->request->post[$key] : $default;
        }
    }

    public function index($key = null, $default = null)
    {
        if ($res = $this->get($key)) {
            return $res;
        } elseif ($res = $this->post($key)) {
            return $res;
        } else {
            return $default;
        }
    }

    public function server($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request->server;
        } else {
            return isset($this->request->server[$key]) ? $this->request->server[$key] : $default;
        }
    }

    public function header($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request->header;
        } else {
            return isset($this->request->header[$key]) ? $this->request->header[$key] : $default;
        }
    }

    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request->cookie;
        } else {
            return isset($this->request->cookie[$key]) ? $this->request->cookie[$key] : $default;
        }
    }

    public function getFd()
    {
        return $this->request->fd;
    }

    public function getStreamId()
    {
        return $this->request->streamId;
    }

    public function getRequestMethod()
    {
        return !empty($this->request->server['request_method']) ? $this->request->server['request_method'] : 'GET';
    }

    public function getRequestUri()
    {
        return !empty($this->request->server['request_uri']) ? $this->request->server['request_uri'] : '';
    }

    public function getPathInfo()
    {
        return !empty($this->request->server['path_info']) ? $this->request->server['path_info'] : '';
    }

    public function getRequestIp()
    {
        $ip = null;
        if (isset($this->request->header['x-real-ip'])) {
            $ip = $this->request->header['x-real-ip'];
        } else {
            $ip = $this->request->server['remote_addr'];
        }
        return $ip;
    }

    public function getRequestBody()
    {
        return $this->request->rawContent();
    }
}