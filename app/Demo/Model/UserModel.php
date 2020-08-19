<?php

namespace App\Demo\Model;

use Core\Base\Model;

class UserModel extends Model
{
    protected $primary = 'id';

    protected $table = 'user';

    protected $useCoroutine = true;

    public function getDBFactory()
    {
        return parent::getDBFactory();
    }
}