<?php

namespace app\exceptions;

use Yii;
use Exception;

class ErrorMessage extends Exception
{
    private $model;
    private $errors;
    private $statusCode;

    public function __construct($model, ?string $message = null, int $statusCode = 422)
    {
        $this->model = $model;
        $this->errors = $model ? $model->getErrors() : [];
        $this->statusCode = $statusCode;
        $message = $message ?? \Yii::t('app', 'badRequest');
        parent::__construct($message, $statusCode);
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getErrors()
    {
        $formattedErrors = [];
        foreach ($this->errors as $field => $messages) {
            foreach ($messages as $message) {
                $formattedErrors[] = [
                    'field' => $field,
                    'message' => $message,
                ];
            }
        }

        return $formattedErrors;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        $statusCode = Yii::$app->response->statusCode = $this->statusCode;

        return [
            'code' => $statusCode,
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->getErrors(),
        ];
    }
}
