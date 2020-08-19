<?php

namespace Core\Base;

use Core\BaseObject;
use Core\Database\Orm\Builder;
use Core\Database\Orm\DB;
use Core\Factory\Factory;

class Model extends BaseObject
{
    protected $primary = null;

    protected $table = null;

    protected $useCoroutine = false;

    /**
     * @return Factory
     */
    public function getDBFactory()
    {
        return Factory::mysql('oi', $this->useCoroutine);
    }

    public function getConn()
    {
        /** @var $queryBuilder Builder */
        $queryBuilder = DB::connector($this->getDBFactory());
        return $queryBuilder->table($this->table);
    }
}