<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\components\CustomException;
use app\core\CoreController;
use app\models\Example;
use app\models\search\ExampleSearch;

/**
 * Example controller for the `v1` module
 */

class ExampleController extends CoreController
{
	public function behaviors()
    {
		$behaviors = parent::behaviors();

		#add your action here
		$behaviors['verbs']['actions'] = array_merge(
			$behaviors['verbs']['actions'],
			[
				'index' => ['get'],
			]
		);

        return $behaviors;
    }

	public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new ExampleSearch();
		$dataProvider = $searchModel->search($params);

		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}

	public function actionList()
	{
		#uncomment below code if you want to show data from mongodb
		// $params = Yii::$app->getRequest()->getBodyParams();

		// $searchModel = new ExampleSearch();
		// $searchModel->load($params);
		// $dataProvider = $searchModel->mongodbSearch($params);

		// CoreController::validateProvider($dataProvider, $searchModel);

		// return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new Example();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

		$model->scenario = $scenario;
		$params['status'] = Constants::STATUS_DRAFT;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				#uncomment below code if you want to insert data to mongodb
				// Yii::$app->mongodb->upsert($model);

				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

	public function actionUpdate()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_UPDATE;

		CoreController::validateParams($params, $scenario);
		
		$model = CoreController::coreFindModelOne(new Example(), $params);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		CoreController::unavailableParams($model, $params);

		$model->scenario = $scenario;

		if ($superadmin = CoreController::superadmin($params)) {
			return $superadmin;
		}

		if ($model->load($params, '') && $model->validate()) {
			CoreController::emptyParams($model);

			if ($model->save()) {
				#uncomment below code if you want to insert data to mongodb
				// Yii::$app->mongodb->upsert($model);

				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

	public function actionDelete()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_DELETE;

		CoreController::validateParams($params, $scenario);

		$model = CoreController::coreFindModelOne(new Example(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		$model->scenario = $scenario;
		$params['status'] = Constants::STATUS_DELETED;

		if ($superadmin = CoreController::superadmin($params)) {
			return $superadmin;
		}

		if ($model->load($params, '') && $model->validate()) {
			CoreController::emptyParams($model);

			if ($model->save()) {
				#uncomment below code if you want to insert data to mongodb
				// Yii::$app->mongodb->upsert($model);

				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}
}