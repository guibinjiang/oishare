<?php
namespace App\Demo\Controller;

use App\Demo\Logic\TestLogic;
use Core\Client\TcpClient;

class TestController extends Controller
{
    public function actionTest()
    {
        $testLogic = _logic(TestLogic::class);
        return $this->format(200, '', $testLogic->test());
    }

    public function actionTcpClient()
    {
        /** @var $tcpClient TcpClient */
        $tcpClient = _class(TcpClient::class);
        $result = $tcpClient->setCoroutineClient(false)
            ->setApp('demo')->setController('test')->setAction('test')
            ->run();
        return $result;
    }
}