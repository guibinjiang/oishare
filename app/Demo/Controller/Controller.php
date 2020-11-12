<?php
namespace App\Demo\Controller;

use Core\Base\CoSingle;
use Core\Swoole\Route\RequestManager;
use Core\Swoole\Route\ResponseManager;

class Controller extends \Core\Base\Controller
{
    public $requestManager = null;

    public $responseManager = null;

    public function __construct()
    {
        parent::__construct();

        $this->requestManager = _class(RequestManager::class);
        $this->responseManager = _class(ResponseManager::class);
    }

    public function format($code, $msg, $result)
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'result' => $result
        ];
    }
}