<?php

namespace Core\Database\Connector;

interface ConnectorIFace
{
    public function dsn();

    public function connect($config = [], $bind = []);

    public function queryOne($sql = '', $bind = []);

    public function queryRow($sql = '', $bind = []);

    public function queryCol($sql = '', $bind = [], $field = '');

    public function queryAll($sql = '', $bind = []);

    public function query($sql = '', $bind = []);

    public function fetchAll();

    public function affectedRows();

    public function prepare();

    public function checkConnect();

    public function selectType();

    public function lastInsertId();
}