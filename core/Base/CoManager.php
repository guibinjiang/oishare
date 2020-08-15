<?php
namespace Core\Base;

use \Swoole\Coroutine as Co;

class CoManager
{
    use \Core\Traits\SingleTrait;

    public $_map = [];

    const PREFIX = 'cid_';

    public function getCid()
    {
        $cid = Co::getCid();
        $cid = self::PREFIX . ($cid == -1 ? 'process' : $cid);
        $this->buildCidMap($cid);
        return $cid;
    }

    public function getPcid()
    {
        $pcid = Co::getPcid();
        $pcid = self::PREFIX . ($pcid == -1 ? 'process' : $pcid);
        return $pcid;
    }

    public function getChildCid($cid = null)
    {
        $cidArr = [$cid];
        if (isset($this->_map[$cid])) {
            foreach ($this->_map[$cid] as $key => $cidItem) {
                if (isset($this->_map[$cidItem])) {
                    $cidArr = array_merge($cidArr, $this->getChildCid($cidItem));
                } else {
                    $cidArr[] = $cidItem;
                }
            }
        }
        return $cidArr;
    }

    public function buildCidMap($cid)
    {
        $pcid = $this->getPcid();
        if (isset($this->_map[$pcid]) && in_array($cid, $this->_map[$pcid])) {
            return true;
        }
        if ($cid != $pcid) {
            $this->_map[$pcid][] = $cid;
        }
        return true;
    }

    public function getCurrentCid($isChild = true)
    {
        $cid = $this->getCid();
        if ($isChild) {
            return $this->getChildCid($cid);
        }
        return $cid;
    }

    public function removeCurrentCidMap($cidArr = [])
    {
        foreach ($cidArr as $cid) {
            unset($this->_map[$cid]);
        }

        $cid = $this->getCid();
        $pcid = $this->getPcid();

        if (isset($this->_map[$pcid]) && in_array($cid, $this->_map[$pcid])) {
            $key = array_search($cid, $this->_map[$pcid]);
            unset($this->_map[$pcid][$key]);
        }

        return true;
    }
}