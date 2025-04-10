<?php

namespace app\components;

use Yii;
use yii\db\StaleObjectException;
use yii\web\ErrorHandler;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use app\exceptions\ErrorMessage;

class CustomErrorHandler extends ErrorHandler
{
    protected function renderException($exception)
    {
        $errors = [];
        $message = Yii::t('app', 'unknownError');
        Yii::$app->response->format = Response::FORMAT_JSON;
        $statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;

        if ($exception !== null) {
            $message =$exception->getMessage();

            if ($exception instanceof ErrorMessage) {
                $statusCode = $exception->getStatusCode();
                $errors = $exception->getErrors();
            } elseif ($exception instanceof StaleObjectException) {
                $statusCode = 409;
                $message = Yii::t('app', 'lockVersionOutdated');
            }

            $response = [
                'code' => $statusCode,
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ];

            if (YII_ENV_DEV && !($exception instanceof ErrorMessage)) {
                $response['trace_for_dev'] = [
                    'exception' => get_class($exception),
                    'trace' => $exception->getTraceAsString(),
                ];
            }

            Yii::$app->response->data = $response;
            Yii::$app->response->statusCode = $statusCode;
        } else {
            Yii::$app->response->data = [
                'code' => 500,
                'success' => false,
                'message' => Yii::t('app', 'exceptionOccured'),
            ];
            Yii::$app->response->statusCode = 500;
        }

        Yii::$app->response->send();
    }
}
