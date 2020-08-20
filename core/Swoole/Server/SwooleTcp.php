<?php
namespace Core\Swoole\Server;

use Core\Base\CoSingle;
use Core\Conf;
use Core\Swoole\Route\RequestManager;
use Core\Swoole\Route\ResponseManager;
use Core\Swoole\Route\Route;
use Core\Swoole\Route\TcpRequestProxy;
use Core\Swoole\Route\TcpResponseProxy;

class SwooleTcp extends SwooleBase
{
    public function run()
    {
        $this->setting = Conf::get('swoole', 'tcp');

        $this->server = new \swoole\server($this->setting['host'], $this->setting['port']);
        $this->server->set([
            'worker_num'        => $this->setting['worker_num'],
            'task_worker_num'   => $this->setting['task_worker_num'],
            'task_ipc_mode '    => $this->setting['task_ipc_mode'],
            'task_max_request'  => $this->setting['task_max_request'],
            'daemonize'         => $this->setting['daemonize'],
            'max_request'       => $this->setting['max_request'],
            'dispatch_mode'     => $this->setting['dispatch_mode'],
            'buffer_output_size'=> $this->setting['buffer_output_size'],
            'open_eof_split'    => $this->setting['open_eof_split'],
            'package_eof'       => $this->setting['package_eof'],
            'reload_async'      => $this->setting['reload_async'],
        ]);

        $this->server->on('Start',        [$this, 'onStart']);
        $this->server->on('WorkerStart',  [$this, 'onWorkerStart']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('WorkerStop',   [$this, 'onWorkerStop']);
        $this->server->on('Receive',      [$this, 'onProcess']);
        $this->server->on('Task',         [$this, 'onTask']);
        $this->server->on('Finish',       [$this, 'onFinish']);
        $this->server->on('Shutdown',     [$this, 'onShutdown']);

        $this->server->start();
    }

    public function onProcess($server, $fd, $from_id, $data)
    {
        //调用接口方法
        $request  = CoSingle::getInstance(TcpRequestProxy::class, $data);
        $response = CoSingle::getInstance(TcpResponseProxy::class, $server, $fd);

        $requestManager  = CoSingle::getInstance(RequestManager::class, $request);
        $responseManager = CoSingle::getInstance(ResponseManager::class, $response);

        try {
            $route = new Route($requestManager, $responseManager);
            $return = $route->execute();
            $return['env']['requestId'] = $request->get['env']['requestId'];
            $responseManager->end($return);
        } catch (\Exception $exc) {
            $responseManager->end($exc);
        }
    }
}

