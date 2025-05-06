<?php

// NOTE: Make sure this file is not accessible when deployed to production
if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('You are not allowed to access this file.');
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../vendor/autoload.php';

defined('YII_DEBUG') or define('YII_DEBUG', $_ENV['YII_DEBUG'] == 'true' ?? false);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/test.php';

(new yii\web\Application($config))->run();
