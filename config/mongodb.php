<?php

return [
    'class' => 'app\components\CustomMongodb',
    'dsn' => $_ENV['MONGODB_DSN'],
    'database' => $_ENV['MONGODB_DATABASE'],
];