<?php

namespace app\core;

/**
 * CoreController functionality for the application core controller.
 * Provides controller functionality for API response handling, CORS, and content negotiation.
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\components\CustomException;
use app\helpers\Constants;
use app\exceptions\ErrorMessage;
use yii\base\DynamicModel;

/**
 * CoreController serves as the base controller for RESTful API endpoints.
 * Provides common functionality for API response handling, CORS, and content negotiation.
 * 
 * Features:
 * - Automatic CORS configuration for cross-domain requests
 * - JSON response formatting
 * - Standardized error handling
 * - CSRF validation configuration
 * - Request method filtering
 * 
 * @property bool $enableCsrfValidation CSRF validation flag, configurable via params
 */
class CoreController extends Controller
{
    /**
     * @var bool CSRF validation status
     */
    public $enableCsrfValidation;

    /**
     * Configures controller behaviors including CORS and content negotiation.
     * Settings are loaded from application parameters.
     * 
     * @return array Array of behaviors
     */
    public function behaviors()
    {
        $this->enableCsrfValidation = Yii::$app->params['request']['enableCsrfValidation'];
        
        return [
            'corsFilter' => [
                'class' => '\yii\filters\Cors',
                'cors' => [
                    'Origin' => Yii::$app->params['cors']['origins'], 
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Request-Origin' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Request-Method' => Yii::$app->params['cors']['requestMethods'],
                    'Access-Control-Allow-Headers' => Yii::$app->params['cors']['allowHeaders'],
                ],
            ],
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'authenticator' => [
                'class' => 'app\components\JwtBearerAuth',
                'except' => Yii::$app->params['jwt']['except'],
            ],
            'verbs' => [
				'class' => 'yii\filters\VerbFilter',
				'actions' => Yii::$app->params['verbsAction'],
			],
        ];
    }

    /**
     * Default action for the controller.
     * Returns a basic success response with service information.
     * 
     * Usage:
     * ```php
     * // In your controller
     * public function actionIndex()
     * {
     *     return CoreController::actionIndex();
     * }
     * ```
     * 
     * @return array API response
     */
    public function actionIndex()
	{
		return [
			'code' => Yii::$app->response->statusCode = 200,
			'success' => true,
			'message' => Yii::$app->params['titleService'].' '.Yii::$app->params['serviceVersion'],
            'data' => [
                [
                    'language' => Yii::$app->language,
                    'version' => Yii::$app->params['serviceVersion'],
                ]
            ],
		];
	}

    /**
     * Alias for actionIndex.
     * Provides the same service information through a different method name.
     * 
     * Usage:
     * ```php
     * // In your controller
     * public function actionIndex()
     * {
     *     return CoreController::coreActionIndex();
     *     // Returns service info in standard format
     * }
     * ```
     * 
     * @return array API response
     */
    public function coreActionIndex()
	{
		return self::actionIndex();
	}

    /**
     * Error action for the controller.
     * Returns an error response with the exception message.
     * 
     * Usage:
     * ```php
     * // In config/web.php
     * 'errorHandler' => [
     *     'errorAction' => 'site/error',
     * ],
     * ```
     * 
     * @return array API response with error details
     */
    public function actionError()
    {
        return [
            'status' => Yii::$app->errorHandler->exception->statusCode,
            'success' => false,
            'message' => Yii::$app->errorHandler->exception->getMessage(),
            'errors' => [],
        ];
    }

    /**
     * Finds a model instance by ID or other parameters.
     * Supports both single ID lookup and complex queries with additional parameters.
     * Automatically handles optimistic locking version field by removing it from results.
     * 
     * Usage:
     * ```php
     * // Find by ID
     * $user = $this->coreFindModelOne(User::class, ['id' => 123]);
     * 
     * // Find with additional conditions
     * $activeUser = $this->coreFindModelOne(
     *     User::class,
     *     ['id' => 123],
     *     ['status' => Constants::STATUS_ACTIVE]
     * );
     * 
     * // Find by other parameters only
     * $adminUser = $this->coreFindModelOne(
     *     User::class,
     *     null,
     *     ['role' => 'admin', 'is_active' => true]
     * );
     * 
     * if ($user === null) {
     *     throw new NotFoundHttpException('User not found');
     * }
     * ```
     * 
     * @param string $model Fully qualified model class name
     * @param array|null $paramsID ID parameters, typically ['id' => value]
     * @param array|null $otherParams Additional query conditions as key-value pairs
     * @return object|null Model instance if found, null otherwise
     */
    public function coreFindModelOne($model, ?array $paramsID, ?array $otherParams = []): ?object
	{
        $id = $paramsID['id'] ?? null;
        $where = [];

        if ($id) {
            $where = ['id' => $id];
        }

        if ($otherParams) {
            $where = array_merge($where, $otherParams);
        }

		if (!empty($where)) {
			$query = $model::find()->where($where);
			if (($modelInstance = $query->one()) !== null) {
                $lockVersion = Constants::OPTIMISTIC_LOCK;

                if (isset($modelInstance->$lockVersion)) {
                    // Hide lock_version on result data for update/delete.
                    unset($modelInstance->$lockVersion);
                }

				return $modelInstance;
			}
		}

		return null;
	}

    /**
     * Finds a model instance by ID or other parameters.
     * Supports with query parameters.
     * Automatically handles optimistic locking version field by removing it from results.
     * 
     * Usage:
     * ```php
     * // Find by ID
     * $user = $this->coreFindModel(User::class, ['id' => 123])->one();
     * 
     * if ($user === null) {
     *     throw new NotFoundHttpException('User not found');
     * }
     * ```
     * 
     * @param string $model Fully qualified model class name
     * @param array|null $params Query parameters, typically ['id' => value]
     * @return object|null Model instance if found, null otherwise
     */
    public function coreFindModel($model, ?array $params): ?object
	{
		if (!empty($params)) {
			$query = $model::find()->where($params);
            if ($query->exists()) {
                $modelInstance = $query;
                $lockVersion = Constants::OPTIMISTIC_LOCK;

                if (isset($modelInstance->$lockVersion)) {
                    // Hide lock_version on result data for update/delete.
                    unset($modelInstance->$lockVersion);
                }

                return $modelInstance;
            }
		}

		return null;
	}

    /**
     * Formats data provider for API response.
     * Standardizes pagination and data format for list endpoints.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreData($dataProvider);
     * ```
     * 
     * @param object $dataProvider Data provider instance
     * @return array API response with pagination
     */
    public function coreData($dataProvider): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => Yii::t('app', 'success'),
            'pagination' => [
                'page' => $dataProvider->pagination->page + 1,
                'totalCount' => $dataProvider->totalCount,
                'total' => max($dataProvider->count, 0),
                'display' => $dataProvider->count,
            ],
            'data' => $dataProvider,
        ];
    }

    /**
     * Formats custom data for API response.
     * Useful for returning non-standard data structures.
     * 
     * Usage:
     * ```php
     * // In controller action
     * $stats = [
     *     'total_users' => User::find()->count(),
     *     'active_users' => User::find()->active()->count()
     * ];
     * return CoreController::coreCustomData($stats, 'Statistics retrieved');
     * ```
     * 
     * @param array $model Model data
     * @param string|null $message Custom message
     * @return array API response
     */
    public function coreCustomData($model=[], ?string $message = null): array
    {
        $response = $model ?? [];
        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => $message ?? Yii::t('app', 'success'),
            'data' => $response,
        ];
    }

    /**
     * Formats success response with model data.
     * Standardizes successful API responses with optional custom message and additional data.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreSuccess(
     *     $model,
     *     Yii::t('app', 'User updated successfully'),
     * );
     * ```
     * 
     * @param array $model Model data
     * @param string|null $message Custom message
     * @param array|null $data Additional data
     * @return array API response
     */
    public function coreSuccess($model=[], ?string $message = null, ?array $data=null): array
    {
        $response[] = $model ?? [];
        $message = $message ?? Yii::t('app', "{$model->scenario}RecordSuccess");

        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => $message,
            'data' => $response,
        ];
    }

    /**
     * Formats error response with model errors.
     * Standardizes validation error responses with custom messages.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreError($model, Yii::t('app', 'Failed to create user'));
     * ```
     * 
     * @param object $model Model instance with validation errors
     * @param string|null $message Custom error message
     * @return array API response
     */
    public function coreError($model, ?string $message = null): array 
    {
        return [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => $message ?? Yii::t('app', "{$model->scenario}RecordFailed"),
            'errors' => isset($model->errors) ? $model : [],
        ];
    }

    /**
     * Formats not found response.
     * Returns a standardized 404 response for missing resources.
     * 
     * Usage:
     * ```php
     * // In controller action
     * if ($model === null) {
     *     return CoreController::coreDataNotFound();
     * }
     * ```
     * 
     * @return array API response with 404 status
     */
    public function coreDataNotFound(): array
    {
        try {
            throw new \Exception(Yii::t('app', 'dataNotFound'));
        } catch (\Exception $e) {
            return [
                'code' => Yii::$app->response->statusCode = 404,
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    /**
     * Formats bad request response.
     * Returns a standardized 400 response for invalid requests.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreBadRequest(
     *     $model,
     *     Yii::t('app', 'Invalid bulk update request')
     * );
     * ```
     * 
     * @param object $model Model instance or error array
     * @param string|null $message Custom error message
     * @return array API response with 400 status
     */
    public function coreBadRequest($model, ?string $message = null): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 400,
            'success' => false,
            'message' => $message ?? Yii::t('app', 'badRequest'),
            'errors' => $model ?? [],
        ];
    }

    /**
     * Validates data provider and search model.
     * Ensures data provider and optional search model are valid before processing.
     * 
     * Usage:
     * ```php
     * // In controller action
     * public function actionList()
     * {
     *     $searchModel = new UserSearch();
     *     $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
     *     
     *     CoreController::validateProvider($dataProvider, $searchModel);
     *     
     *     return CoreController::coreData($dataProvider);
     * }
     * ```
     * 
     * @param object $dataProvider Data provider instance
     * @param object|null $searchModel Search model instance
     * @return array|bool API error response or false if validation passes
     * @throws ErrorMessage when validation fails
     */
    public function validateProvider($dataProvider, $searchModel = null): array|bool
    {
        $model = new DynamicModel();
        
        if (isset($dataProvider->errors)) {
            $errors = $dataProvider->errors;

            foreach($errors as $error) {
                $model->addError($error['field'], $error['message']);
            }

            throw new ErrorMessage($model, Yii::t('app', 'validationFailed'), 422);
        }

        return false;
    }

    /**
     * Validates request parameters.
     * Ensures required parameters are present and correctly formatted.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::validateParams($params, Constants::SCENARIO_UPDATE);
     * ```
     * 
     * @param array|null $params Request parameters
     * @param string $scenario Validation scenario
     * @return array|bool API error response or false if validation passes
     * @throws ErrorMessage when validation fails
     */
    public function validateParams(?array $params, string $scenario = 'default'): array|bool
    {
        $idKey = 'id';
        $messageError = null;
        
        // Create dynamic model with attributes
        $attributes = array_keys($params);
        $model = new DynamicModel($attributes);
        
        // Add validation rules
        if (isset($params[$idKey])) {
            $id = $params[$idKey];
            unset($params[$idKey]);

            if (!is_numeric($id) || intval($id) != $id) {
                $model->addError($idKey, Yii::t('app', 'integer', ['label' => $idKey]));
            }

            if ($scenario === Constants::SCENARIO_UPDATE && empty($params)) {
                $model->addError($idKey, Yii::t('app', 'emptyParams'));
            }
        } else {
            $messageError = Yii::t('app', 'validationFailed');
            $model->addError($idKey, Yii::t('app', 'required', ['label' => $idKey]));
        }

        if ($model->hasErrors()) {
            throw new ErrorMessage($model, Yii::t('app', 'validationFailed'), 422);
        }

        return false;
    }

    /**
     * Checks for empty parameters in update or delete scenarios.
     * Prevents unnecessary database operations when no changes are made.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::emptyParams($model);
     * 
     * // Continue with update...
     * ```
     * 
     * @param object $model Model instance
     * @param string|null $scenario Validation scenario
     * @return array|bool API error response or false if changes detected
     * @throws ErrorMessage when no changes detected
     */
    public function emptyParams($model, $scenario = null): bool|array
    {
        $message = null;
        $scenario = $scenario ?? Constants::SCENARIO_UPDATE;
        $optimisticLock = Constants::OPTIMISTIC_LOCK;

        $getDirtyAttributes = $model->getDirtyAttributes();
        unset($getDirtyAttributes['id']);
        unset($getDirtyAttributes[$optimisticLock]);

        if (empty($getDirtyAttributes) && in_array($scenario, [Constants::SCENARIO_UPDATE, $scenario], true)) {
            $message = Yii::t('app', 'noRecordUpdated');
        }

        if ($scenario === Constants::SCENARIO_DELETE && $model->status === $model->getOldAttribute('status')) {
            $message = Yii::t('app', 'noRecordDeleted');
        }

        if ($message) {
            throw new ErrorMessage($model, $message, 400);
        }

        return false;
    }

    /**
     * Checks for unavailable parameters in the request.
     * Validates that all requested parameters are valid for the model.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::unavailableParams($model, $params);
     * 
     * ```
     * 
     * @param object $model Model instance
     * @param array|null $params Request parameters
     * @return array|bool API error response or false if all parameters are valid
     * @throws ErrorMessage when invalid parameters detected
     */
    public function unavailableParams($model, ?array $params): array|bool
    {
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model, $params)) {
            throw new ErrorMessage($unavailableParams, Yii::t('app', 'validationFailed'), 422);
        }

        return false;
    }

    /**
     * Checks if the request requires superadmin privileges.
     * Validates if the current user has permission to perform status-restricted operations.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::superadmin($params);
     * 
     * ```
     * 
     * @param array|null $params Request parameters containing status
     * @return array|bool Error response array if unauthorized, false if authorized
     * @throws ErrorMessage with 403 status code if unauthorized
     */
    public function superadmin(?array $params): array|bool
    {
        $status = (int)($params['status'] ?? null);
        $restrictStatus = Constants::RESTRICT_STATUS_LIST;

        if (
            !$this->isSuperAdmin()
            && $status !== null
            && in_array($status, $restrictStatus, true)
        ) {
            $model = new DynamicModel();
            throw new ErrorMessage($model, Yii::t('app', 'superadminOnly'), 403);
        }

        return false;
    }

    /**
     * Checks if the current user has superadmin role.
     * Used for role-based access control in restricted operations.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::isSuperAdmin();
     * 
     * ```
     * 
     * @return bool True if user has superadmin role, false otherwise
     */
    public function isSuperAdmin(): bool
    {
        $roles = Yii::$app->session->get('roles', []);
        return !in_array('superadmin', $roles, true);
    }
}
