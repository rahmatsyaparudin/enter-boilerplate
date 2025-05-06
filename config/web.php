<?php

/*
 * Web configuration for the application.
 * 
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

$params = require __DIR__ . '/params.php';
// $mongodb = require __DIR__ . '/mongodb.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic'.$params['extraCookies'],
    'name' => $params['titleService'],
    'timeZone' => $params['timestamp']['timeZone'],
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => $params['language']['default'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'params' => $params,
    'components' => [
        'db' => $db,
        // 'mongodb' => $mongodb,
        'coreAPI' => [
            'class' => 'app\core\CoreAPI',
        ],
        'request' => [
            #insert a secret key in the following (if it is empty) - this is required by cookie validation
            // 'cookieValidationKey' => 'rHaRkLSi4OMU-_Gi8LBn0Fh8IcqrYJP7'.$params['extraCookies'],
            'enableCsrfValidation' => $params['request']['enableCsrfValidation'],
            'enableCookieValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;

                if (!YII_ENV_DEV){
                    if ($response->data !== null && Yii::$app->request->get('suppress_response_code')) {
                        $response->data = [
                            'success' => $response->isSuccessful,
                            'data' => $response->data,
                        ];
                        $response->statusCode = 200;
                    } else {
                        $response->data['success'] = $response->isSuccessful;
                        unset($response->data['type']);

                        if (!$response->isSuccessful) {
                            unset($response->data['name']);
                        }

                        if ($response->statusCode == 405) {
                            unset($response->data['status']);
                            $response->data['errors'] = [];
                            $response->data['code'] = $response->statusCode;
                        }
                    }
                }
            },
        ],
        'errorHandler' => [
            // 'errorAction' => 'site/error',
            'class' => 'app\components\CustomErrorHandler',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
                '/v1' => 'site/index',
                '/v1/index' => 'site/index',
            ],
        ],
        'pagination' => [
            'class' => 'yii\data\Pagination',
            'defaultPageSize' => 10,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/translation',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                    'on missingTranslation' => ['app\components\TranslationEventHandler', 'handleMissingTranslation']
                ],
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    #adding for query logging and prevent logging of $_SERVER, $_SESSION, etc.
                    'categories' => ['yii\db\Command::execute'],
                    'logFile' => '@runtime/logs/sql.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 10,
                    'logVars' => [],  // Prevents logging of $_SERVER, $_SESSION, etc.
                ],
            ],
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            #send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'enableSession' => false,
            'loginUrl' => null
        ],
    ],
    'modules' => [
        'v1' => [
            'class' => 'app\modules\v1\Module',
        ],
    ],
    'on beforeAction' => function ($event ) use ($params) {
        $req = Yii::$app->request->getBodyParams();
        if (isset($req['language'])) {
            Yii::$app->language = $req['language'];
            if(!in_array(Yii::$app->language, $params['language']['list'])) {
                Yii::$app->language = $params['language']['default'];
            }
        }
    },
    'on beforeRequest' => function ($event) use ($params) {
        $defaultLang = $params['language']['default'];
        $lang = Yii::$app->request->getHeaders()->get('Accept-Language');

        Yii::$app->language = $lang ?? $defaultLang;
        if(!in_array(Yii::$app->language, $params['language']['list'])) {
            Yii::$app->language = $defaultLang;
        }
    },
];

if (YII_ENV_DEV) {
    #configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        #uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        #uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
