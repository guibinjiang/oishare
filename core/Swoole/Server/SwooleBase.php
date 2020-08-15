<?php

namespace Core\Swoole\Server;

use Core\Swoole\IFace\SwooleServerIFace;

class SwooleBase implements SwooleServerIFace
{
    protected $setting;

    protected $server;

    public function onStart()
    {
    }

    public function onConnect()
    {

    }

    public function onWorkerStart()
    {

    }

    public function onManagerStart()
    {

    }

    public function onWorkerStop()
    {

    }

    public function onTask()
    {

    }

    public function onFinish()
    {

    }

    public function onShutdown()
    {

    }

    public function onClose()
    {

    }
}