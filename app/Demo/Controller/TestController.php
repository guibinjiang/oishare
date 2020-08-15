<?php
namespace App\Demo\Controller;

use App\Demo\Logic\TestLogic;
use Core\Base\CoSingle;

class TestController extends Controller
{
    public function actionTest()
    {
        $testLogic = CoSingle::getInstance(TestLogic::class);
        return $this->format(200, '', $testLogic->test());
    }
}