<?php

namespace app\core;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\components\CustomException;
use app\exceptions\ErrorMessage;

class CoreAPI 
{
	public function UTCTimestamp(): string
	{
		return gmdate(Yii::$app->params['timestamp']['UTC']);
	}

	public function timestamp(): string
	{
		return gmdate(Yii::$app->params['timestamp']['local']);
	}

	public function getUsername(): string
	{
		return Yii::$app->session->get('username') ?? 'system';
	}

	public function superAdmin(): bool
    {
        $roles = Yii::$app->session->get('roles', []);
        return !in_array('superadmin', $roles, true);
    }

	public function unavailableParams($model, ?array $params): object|bool
	{
		$allowedParams = [];
		unset($params['id']);

        $rules = $model->rules();

		foreach ($rules as $rule) {
			$allowedParams = array_merge($allowedParams, $rule[0]);
		}

		$unsupportedParams = array_diff_key($params, array_flip($allowedParams));
		
		if (!empty($unsupportedParams)) {
			foreach ($unsupportedParams as $key => $value) {
				$model->addError($key, Yii::t('app', 'invalidField', ['label' => $key]));
			}

			return $model;
		}

		return false;
	}

	public function unauthorizedAccess(?string $message = null): array
    {
		throw new ErrorMessage(null, Yii::t('app', 'unauthorizedAccess'), 401);
    }

	public function serverError(?string $message = null): array
    {
		throw new ErrorMessage(null, Yii::t('app', 'serverError'), 500);
    }

	public function setMongodbSyncFailed($model): void
	{
		if ($model->id !== null) {
			$model->sync = 1;
			$model->save(false);
		}
	}

	public function generateUniqueString($length = 8) {
        $microtime = microtime(true);
        $timeString = substr(base_convert($microtime, 10, 36), -4);
        $randomString = substr(bin2hex(random_bytes($length)), 0, $length - 4);
        return $timeString . $randomString;
    }
}