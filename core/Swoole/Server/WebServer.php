<?php
namespace Core\Swoole\Server;

use Core\Conf;
use Core\Base\CoSingle;
use Core\Swoole\Route\HttpRequestProxy;
use Core\Swoole\Route\HttpResponseProxy;
use Core\Swoole\Route\RequestManager;
use Core\Swoole\Route\ResponseManager;
use Core\Swoole\Route\Route;

class WebServer extends SwooleBase
{
    public function run()
    {
        $request = new HttpRequestProxy();
        $response = new HttpResponseProxy();
        $this->onProcess($request, $response);
    }

    /**
     * @param $request HttpRequestProxy
     * @param $response HttpResponseProxy
     * @return void
     */
    public function onProcess($request, $response)
    {
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->end();
        }

        /** @var $requestManager RequestManager */
        $requestManager = CoSingle::getInstance(RequestManager::class, $request);
        /** @var $responseManager ResponseManager */
        $responseManager = CoSingle::getInstance(ResponseManager::class, $response);

        try {
            $route = new Route($requestManager, $responseManager);
            $return = $route->execute();
            $responseManager->end($return);
        } catch (\Exception $exc) {
            $responseManager->end($exc);
        }
    }
}