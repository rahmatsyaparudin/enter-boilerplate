<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actionIndex()
    {
        // \Yii::$app->language = Yii::$app->params['language']['default']; // set language output
        $data = [
            'code' => 200,
            'success' => true,
            'message' => Yii::t('app', 'success'),
            'data' => [
                [
                    'app' => Yii::$app->params['codeApp'],
                    'service' => Yii::$app->params['titleService'],
                    'version' => Yii::$app->params['serviceVersion'],
                    'language' => Yii::$app->language,
                    'environment' => YII_ENV_DEV ? 'development' : 'production',
                ]
            ],
        ];

        return $data;
    }

    public function actionError()
    {
        $data = [
            'status' => Yii::$app->errorHandler->exception->statusCode,
            'success' => false,
            'message' => Yii::$app->errorHandler->exception->getMessage(),
            'errors' => [],
        ];

        return $data;
    }

    public function actionVersion()
    {
        $data = [
            'code' => 200,
            'success' => true,
            'message' => Yii::t('app', 'success'),
            'data' => [
                [
                    'version' => Yii::getVersion(),
                ]
            ],
        ];

        return YII_ENV_DEV ? $data : [];
    }
}
