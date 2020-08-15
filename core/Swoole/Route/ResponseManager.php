<?php
namespace Core\Swoole\Route;

use Core\BaseObject;

class ResponseManager extends BaseObject
{
    /**
     * @var \Swoole\Http\response
     */
    public $response;

    public function __construct($response = null)
    {
        parent::__construct();

        $this->response = $response;
    }

    public function end($result)
    {
        $this->response->end(json_encode($result));
    }
}