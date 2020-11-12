<?php

namespace App\Demo\Logic;

use App\Demo\Model\UserModel;
use Core\Base\CoSingle;
use Core\Base\Logic;

class TestLogic extends Logic
{
    public function Test()
    {
        $userModel = _model(UserModel::class);
        $list = $userModel->getConn()->getAll();
        return $list;
    }
}