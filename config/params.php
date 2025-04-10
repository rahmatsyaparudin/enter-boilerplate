<?php

$params_json = require __DIR__ . '/params_json.php';

return array_merge($params_json, [  
    'titleService' => 'enterkomputer-bolier-plate',
    'serviceVersion' => 'V1',
    'extraCookies' => 'name-cookie-01',
    // DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
    'codeApp' => 'enterEDC',
    #'migrateFresh' => !YII_ENV_DEV,
    'migrateFresh' => true,
    'timestamp' => [
        'timeZone' => 'Asia/Jakarta',
        'UTC' => 'Y-m-d\TH:i:s\Z',
        'local' => 'Y-m-d H:i:s'
    ],
    'language' => [
        'default' => 'en',
        'list' => [
            'en', 
            'id',
        ],
    ],
    'jwt' => [
        'key' => 'secret-key-harus-panjang-256-bit',
        'algorithm' => 'HS256',
        'expire' => '+1 hour',
        'issuer' => 'https://sso.dev-enterkomputer.com',
        'audience' => 'https://sso.dev-enterkomputer.com',
        'id' => 'sso-auth-v1',
        'request_time' => '+5 minutes',
        // DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
        // 'except' => YII_ENV_DEV ? ['*'] : ['index'],
        'except' => YII_ENV_DEV ? ['*'] : ['index'],
    ],
    'request' => [
        // DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
        // 'enableCsrfValidation' => !YII_ENV_DEV,
        'enableCsrfValidation' => false,
    ],
    'cors' => [
        'requestMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'origins' => ['http://localhost:5173', 'https://entersys.dev-enterkomputer.com'],
        'allowHeaders' => ['Origin','Content-Type','Authorization','Accept-Language'],
    ],
    'pagination' => [
        'pageSize' => 10,
        'sortDir' => SORT_DESC,
    ],
    'verbsAction' => [
        'index' => ['get'],
        'data' => ['post'],
        'create' => ['post'],
        'update' => ['put'],
        'delete' => ['delete'],
        'view' => ['post'],
    ],
    'mailer' => [
        'adminEmail' => 'admin@example.com',
        'senderEmail' => 'noreply@example.com',
        'senderName' => 'Example.com mailer',
    ],
]);
