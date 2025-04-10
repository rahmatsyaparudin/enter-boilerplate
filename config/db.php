<?php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'class' => 'yii\db\Connection',
    'dsn' => $_ENV['DB_DEFAULT_DSN'],
    'username' => $_ENV['DB_DEFAULT_USERNAME'],
    'password' => $_ENV['DB_DEFAULT_PASSWORD'],
    'charset' => 'utf8',
    'enableLogging' => true,
    'enableProfiling' => true,

    // Schema cache options (for production environment)
    'enableSchemaCache' => YII_ENV_DEV,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];