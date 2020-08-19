<?php

define('ROOT_PATH',             __DIR__ . '/');
define('APP_PATH',              __DIR__ . '/app/');
define('CONF_PATH',             __DIR__ . '/config/');
define('CORE_PATH',             __DIR__ . '/core/');
define('HELP_PATH',             __DIR__ . '/helper/');
define('LOG_PATH',              __DIR__ . '/log/');
define('CONSTS_PATH',           __DIR__ . '/consts/');

define('DEV_ENV', 'local');

ini_set('display_errors', true);

require_once CORE_PATH . '/Bootstrap.php';

$app = new Core\Swoole\Server();
$app->run();
