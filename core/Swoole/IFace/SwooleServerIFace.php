<?php

namespace Core\Swoole\IFace;

interface SwooleServerIFace
{
    public function onStart();

    public function onConnect();

    public function onWorkerStart();

    public function onManagerStart();

    public function onWorkerStop();

    public function onTask();

    public function onFinish();

    public function onShutdown();

    public function onClose();
}