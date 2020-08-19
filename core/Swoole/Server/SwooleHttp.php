<?php
namespace Core\Swoole\Server;

use Core\Conf;
use Core\Base\CoSingle;
use Core\Swoole\Route\RequestManager;
use Core\Swoole\Route\ResponseManager;
use Core\Swoole\Route\Route;

class SwooleHttp extends SwooleBase
{
    public function run()
    {
        $this->setting = Conf::get('swoole', 'http');

        $this->server = new \Swoole\Http\Server($this->setting['host'], $this->setting['port']);
        $this->server->set([
            'worker_num'        => $this->setting['worker_num'],
            'task_worker_num'   => $this->setting['task_worker_num'],
            'task_ipc_mode '    => $this->setting['task_ipc_mode'],
            'task_max_request'  => $this->setting['task_max_request'],
            'max_request'       => $this->setting['max_request'],
            'dispatch_mode'     => $this->setting['dispatch_mode'],
            'reload_async'      => $this->setting['reload_async'],
        ]);
        $this->server->on('Start'       , [$this, 'onStart']);
        $this->server->on('WorkerStart' , [$this, 'onWorkerStart']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('WorkerStop'  , [$this, 'onWorkerStop']);
        $this->server->on('Request'     , [$this, 'onProcess']);
        $this->server->on('Task'        , [$this, 'onTask']);
        $this->server->on('Finish'      , [$this, 'onFinish']);
        $this->server->on('Shutdown'    , [$this, 'onShutdown']);

        $this->server->start();
    }

    /**
     * @param $request \swoole\http\Request
     * @param $response \swoole\http\Response
     * @return void
     */
    public function onProcess($request, $response)
    {
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->end();
            return ;
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