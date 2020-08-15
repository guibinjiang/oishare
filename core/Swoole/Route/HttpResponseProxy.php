<?php
namespace Core\Swoole\Route;

use Core\BaseObject;

class HttpResponseProxy
{
    public function __construct($response = null)
    {
    }

    public function end($result = null)
    {
        echo '<pre>';
        print_r($result);
        exit;
    }
}