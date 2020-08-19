<?php
namespace Core\Swoole;

class Server
{
    public function run()
    {
        $opt = getopt('m:');
        $opt['m'] = isset($opt['m']) ? $opt['m'] : '';
        switch ($opt['m']) {
            case 'http':
                define('ServerModel', 'HTTP');
                (new \Core\Swoole\Server\SwooleHttp())->run();
                break;
            case 'tcp':
                define('ServerModel', 'TCP');
                (new \Core\Swoole\Server\SwooleTcp())->run();
                break;
            case 'webSocket':
                define('ServerModel', 'WEBSOCKER');
                (new \Core\Swoole\Server\SwooleWebSocket())->run();
                break;
            default:
                define('ServerModel', 'WEB');
                (new \Core\Swoole\Server\WebServer())->run();
                break;
        }
    }
}