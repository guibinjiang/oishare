<?php
namespace Core\Swoole;

class Server
{
    public function run()
    {
        $opt = getopt('m:');
        switch ($opt['m']) {
            case 'http':
                (new \Core\Swoole\Server\SwooleHttp())->run();
                break;
            case 'rpc':
                (new \Core\Swoole\Server\SwooleRPC())->run();
                break;
            case 'webSocket':
                (new \Core\Swoole\Server\SwooleRPC())->run();
                break;
            default:
                (new \Core\Swoole\Server\WebServer())->run();
                break;
        }
    }
}