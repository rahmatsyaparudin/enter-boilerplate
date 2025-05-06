<?php

namespace app\core;

/**
 * CoreModel functionality for the application core.
 * Provides model utility methods for class name retrieval and null-safe value conversion.
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

use Yii;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use yii\helpers\StringHelper;
use app\exceptions\ErrorMessage;
use yii\base\DynamicModel;

/**
 * Class CoreModel
 * @package app\core
 */
class CoreModel 
{
    /**
     * Gets the short class name without namespace.
     * Useful for getting clean model names for logging or display.
     * 
     * Usage:
     * ```php
     * $modelName = CoreModel::getModelClassName($user);
     * // If $user is instance of app\models\User
     * // Returns: 'User'
     * ```
     * 
     * @param object $model The model instance to get class name from
     * @return string The class name without namespace
     */
    public static function getModelClassName($model): string
    {
        return StringHelper::basename(get_class($model));
    }

    /**
     * Safely converts string 'null' and empty string values to actual null.
     * Useful for handling form inputs and API data that might represent null as strings.
     * 
     * Usage:
     * ```php
     * $value = CoreModel::nullSafe('null'); // Returns: null
     * $value = CoreModel::nullSafe('');     // Returns: null
     * $value = CoreModel::nullSafe('test'); // Returns: 'test'
     * ```
     * 
     * @param string|null $value The value to check
     * @return string|null Original value or null if value represents null
     */
    public static function nullSafe(?string $value = null): ?string
    {
        return (is_string($value) && strtolower($value) === 'null') || $value === '' ? null : $value;
    }

    /**
     * Checks if a value represents null in various formats.
     * More comprehensive than nullSafe, checks both actual null and string representation.
     * 
     * Usage:
     * ```php
     * CoreModel::isNullString(null);      // Returns: true
     * CoreModel::isNullString('null');    // Returns: true
     * CoreModel::isNullString('NULL');    // Returns: true
     * CoreModel::isNullString('value');   // Returns: false
     * ```
     * 
     * @param mixed $value The value to check
     * @return bool True if value represents null, false otherwise
     */
    public static function isNullString($value): bool
    {
        return (is_null($value) || (is_string($value) && strtolower($value) === 'null'));
    }

    /**
     * Safely purifies HTML content and removes all tags.
     * Useful for sanitizing user input to prevent XSS attacks.
     * 
     * Usage:
     * ```php
     * $safeText = CoreModel::htmlPurifier('<p>Hello <script>alert("xss")</script></p>');
     * // Returns: 'Hello'
     * ```
     * 
     * @param string|null $value The value to purify
     * @return string|null Purified string with all HTML tags removed
     */
    public static function htmlPurifier(?string $value): ?string
    {
        return $value === null ? null : self::nullSafe(strip_tags(HtmlPurifier::process($value)));
    }

    /**
     * Purifies HTML content while preserving allowed tags.
     * Similar to htmlPurifier but keeps structural HTML intact.
     * 
     * Usage:
     * ```php
     * $safeHtml = CoreModel::contentPurifier('<p>Hello <script>alert("xss")</script></p>');
     * // Returns: '<p>Hello </p>'
     * ```
     * 
     * @param string|null $value The value to purify
     * @return string|null Purified string with safe HTML tags
     */
    public static function contentPurifier(?string $value): ?string
    {
        return $value === null ? null : self::nullSafe(HtmlPurifier::process($value));
    }

    /**
     * Ensures a value is a valid array.
     * Returns empty array for null or non-array values.
     * 
     * Usage:
     * ```php
     * $array = CoreModel::ensureArray(null);     // Returns: []
     * $array = CoreModel::ensureArray('string'); // Returns: []
     * $array = CoreModel::ensureArray([1,2,3]);  // Returns: [1,2,3]
     * ```
     * 
     * @param mixed $array The value to check
     * @return array Valid array or empty array
     */
    public static function ensureArray($array) {
        return empty($array) || !is_array($array) ? [] : $array;
    }

    /**
     * Recursively purifies all elements in an array.
     * Useful for sanitizing arrays of user input.
     * 
     * Usage:
     * ```php
     * $data = ['name' => '<p>John</p>', 'email' => '<script>alert("xss")</script>email@test.com'];
     * $safe = CoreModel::purifyArray($data);
     * // Returns: ['name' => 'John', 'email' => 'email@test.com']
     * ```
     * 
     * @param array|null $array Array to purify
     * @return array|null Purified array or null if input is not an array
     */
    public static function purifyArray($array)
    {
        return is_array($array) ? array_map([__CLASS__, 'purifyObject'], $array) : null;
    }

    /**
     * Purifies all values in an object or array.
     * Helper method for purifyArray, handles individual elements.
     * 
     * Usage:
     * ```php
     * $object = ['text' => '<p>Test</p>'];
     * $safe = CoreModel::purifyObject($object);
     * // Returns: ['text' => 'Test']
     * ```
     * 
     * @param mixed $object Object or array to purify
     * @return array|null Purified array or null if input is null
     */
    public static function purifyObject($object)
    {
        return is_null($object) ? null : array_map([__CLASS__, 'htmlPurifier'], $object);
    }

    /**
     * Creates a case-insensitive LIKE filter for database queries.
     * Automatically adds wildcards around search terms and handles spaces.
     * 
     * Usage:
     * ```php
     * $query->andFilterWhere(CoreModel::setLikeFilter('John', 'name'));
     * // Generates: WHERE name ILIKE '%John%'
     * 
     * $query->andFilterWhere(CoreModel::setLikeFilter('John Doe', 'name'));
     * // Generates: WHERE name ILIKE '%John%Doe%'
     * ```
     * 
     * @param string|null $value Search value
     * @param string $field Database field name
     * @return array Query condition array
     */
    public static function setLikeFilter(?string $value = null, ?string $field = 'name'): array
    {
        return ['ilike', $field, $value ? '%' . str_replace(' ', '%', trim($value)) . '%' : null, false];
    }

    /**
     * Checks if a status is in the restricted list.
     * Used to prevent certain operations on items with restricted status.
     * 
     * Usage:
     * ```php
     * if (CoreModel::isRestrictedStatus($model->status)) {
     *     throw new Exception('Cannot modify item with restricted status');
     * }
     * ```
     * 
     * @param int $status Status value to check
     * @return bool True if status is restricted
     */
    public static function isRestrictedStatus(int $status): bool
    {
        return in_array($status, Constants::RESTRICT_STATUS_LIST, true);
    }

    /**
     * Validates if a string is valid JSON.
     * Useful for validating JSON fields before saving to database.
     * 
     * Usage:
     * ```php
     * if (CoreModel::isJsonString($value)) {
     *     // Process valid JSON
     * } else {
     *     throw new Exception('Invalid JSON format');
     * }
     * ```
     * 
     * @param string $value String to validate as JSON
     * @return bool True if string is valid JSON
     */
    public static function isJsonString($value)
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Gets validation rules for status field.
     * Defines standard validation rules for status attributes including default value,
     * type checking, range validation, and status transition validation.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getStatusRules($this)
     *     );
     * }
     * ```
     * 
     * @param object $model Model instance that contains the status attribute
     * @param array $list Optional custom status list, defaults to Constants::STATUS_LIST
     * @return array Array of validation rules for status field
     */
    public static function getStatusRules($model, ?array $list=[]): array
    {
        $list = !empty($list) ? $list : Constants::STATUS_LIST;
        
        return [
            [['status'], 'default', 'value' => Constants::STATUS_DRAFT],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => array_keys($list)],
            [['status'], 'filter', 'filter' => 'intval'],
            [
                ['status'],
                function ($attribute, $params) use ($model) {
                    self::validateStatusUpdate($attribute, $params, $model);
                },
            ],
        ];
    }

    /**
     * Gets validation rules for master_id and sync_master fields.
     * 
     * Validation rules include:
     * - master_id: defaults to Yii::$app->params['dbDefault']['masterID'], must be an integer
     * - sync_master: defaults to Yii::$app->params['dbDefault']['syncMaster'], must be an integer
     * 
     * @return array Array of validation rules
     */
    public static function getMasterRules(): array
    {
        return [
            [['master_id'], 'default', 'value' => Yii::$app->params['dbDefault']['masterID']],
            [['sync_master'], 'default', 'value' => Yii::$app->params['dbDefault']['syncMaster']],
            [['master_id', 'sync_master'], 'integer', 'skipOnEmpty' => true, 'skipOnError' => true],
        ];
    }

    /**
     * Gets validation rules for slave_id and sync_slave fields.
     * 
     * Validation rules include:
     * - slave_id: defaults to Yii::$app->params['dbDefault']['slaveID'], must be an integer
     * - sync_slave: defaults to Yii::$app->params['dbDefault']['syncSlave'], must be an integer
     * 
     * @return array Array of validation rules
     */
    public static function getSlaveRules(): array
    {
        return [
            [['slave_id'], 'default', 'value' => Yii::$app->params['dbDefault']['slaveID']],
            [['sync_slave'], 'default', 'value' => Yii::$app->params['dbDefault']['syncSlave']],
            [['slave_id', 'sync_slave'], 'integer', 'skipOnEmpty' => true, 'skipOnError' => true],
        ];
    }

    /**
     * Validates that an attribute is an array.
     * Used to ensure attributes that should contain arrays are properly formatted.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['tags'], function($attribute) {
     *             CoreModel::validateAttributeArray($this, $attribute, 'Tags');
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute to validate
     * @param string $label Human-readable label for error messages
     * @return void
     */
    public static function validateAttributeArray($model, $attribute, $label)
    {
        if (!is_array($model->$attribute)) {
            $model->addError($attribute, Yii::t('app', 'array', ['label' => $label]));
            throw new ErrorMessage($model, Yii::t('app', 'validationFailed'), 422);
        }
    }

    /**
     * Validates that an attribute is either an array or null.
     * Similar to validateAttributeArray but allows null values.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['optional_tags'], function($attribute) {
     *             CoreModel::validateAttributeArrayOrNull($this, $attribute, 'Optional Tags');
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute to validate
     * @param string $label Human-readable label for error messages
     * @return void
     */
    public static function validateAttributeArrayOrNull($model, $attribute, $label)
    {
        if (!is_array($model->$attribute)) {
            if (self::isNullString($model->$attribute)) {
                return;
            }

            $model->addError($attribute, Yii::t('app', 'array', ['label' => $label]));
            throw new ErrorMessage($model, Yii::t('app', 'validationFailed'), 422);
        }
    }

    /**
     * Gets validation rules for sync_mdb field.
     * Defines rules for MongoDB synchronization status tracking.
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getMongoDbSyncRules($this),
     *     );
     * }
     * ```
     * 
     * @param object|null $model Optional model instance
     * @return array Array of validation rules for sync field
     */
    public static function getMongoDbSyncRules($model=null): array
    {
        return [
            [['sync_mdb'], 'default', 'value' => null],
            [['sync_mdb'], 'integer'],
        ];
    }

    /**
     * Gets validation rules for optimistic locking.
     * Implements version-based concurrency control.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getLockVersionRules($this, Constants::SCENARIO_UPDATE),
     *     );
     * }
     * ```
     * 
     * @param object|null $model Optional model instance
     * @param string|null $requiredOn Scenario where version is required
     * @return array Array of validation rules for version field
     */
    public static function getLockVersionRules($model=null, $requiredOn=null): array
    {
        return [
            [[Constants::OPTIMISTIC_LOCK], 'required', 'on' => $requiredOn ?? Constants::SCENARIO_UPDATE_LIST],
            [[Constants::OPTIMISTIC_LOCK], 'integer'],
            [[Constants::OPTIMISTIC_LOCK], 'default', 'value' => 1, 'on' => [Constants::SCENARIO_CREATE]],
        ];
    }

    /**
     * Gets basic version field validation rules.
     * Simplified version of getLockVersionRules without scenario handling.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getLockVersionRulesOnly(),
     *     );
     * }
     * ```
     * 
     * @return array Array of basic validation rules for version field
     */
    public static function getLockVersionRulesOnly(): array
    {
        return [
            [[Constants::OPTIMISTIC_LOCK], 'integer'],
        ];
    }

    /**
     * Gets validation rules for pagination parameters.
     * Validates common pagination fields like page number and size.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getPaginationRules($this),
     *     );
     * }
     * ```
     * 
     * @param object $model Model instance
     * @return array Array of validation rules for pagination fields
     */
    public static function getPaginationRules($model): array
    {
        return [
			[['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
            [['sort_dir', 'sort_by'], 'string'],
            [['page', 'page_size'], 'integer'],
            [['page'], function ($attribute, $params) use ($model) {
                if ($model->$attribute <= 0) {
                    $model->addError($attribute, Yii::t('app', 'pageMustBeGreaterThanZero'));
                    return;
                }
            }],
        ];
    }

    /**
     * Configures pagination settings for data provider.
     * Handles page size limits and default values.
     * 
     * Usage:
     * ```php
     * $dataProvider->setPagination(
     *     CoreModel::setPagination($params, $dataProvider)
     * );
     * ```
     * 
     * @param array|null $params Request parameters containing pagination info
     * @param object $dataProvider DataProvider instance
     * @return array Pagination configuration array
     */
    public static function setPagination(?array $params, $dataProvider): array {
        $pageSize = min((int)$dataProvider->getTotalCount(), (int)($params['page_size'] ?? Yii::$app->params['pagination']['pageSize']));

        return [
            'page' => (int)($params['page'] ?? 1) - 1,
            'pageSize' => $pageSize,
            'defaultPageSize' => $pageSize,
        ];
    }

    /**
     * Configures sorting settings for data provider.
     * Handles sort direction and default sort field.
     * 
     * Usage:
     * ```php
     * $dataProvider->setSort(
     *     CoreModel::setSort($params)
     * );
     * ```
     * 
     * @param array|null $params Request parameters containing sort info
     * @return array Sort configuration array
     */
    public static function setSort(?array $params): array
    {
        $sortBy = $params['sort_by'] ?? 'id';
        $sort = $params['sort_dir'] ?? 'desc';
        $sortDir = match ($sort) {
            'asc' => SORT_ASC,
            'desc' => SORT_DESC,
            default => SORT_DESC,
        };

        return [
            'defaultOrder' => [
                $sortBy => $sortDir,
            ],
        ];
    }

    /**
     * Validates status transitions for a model.
     * Internal method used by getStatusRules() to ensure status changes follow allowed paths.
     * 
     * @param string $attribute Name of the status attribute
     * @param array|null $params Additional parameters
     * @param object $model Model instance being validated
     * @return void
     * @internal
     */
    protected static function validateStatusUpdate(string $attribute, ?array $params, $model): void
    {
        $newStatus = $model->$attribute;
        $oldStatus = $model->getOldAttribute($attribute);
        $statusList = Constants::STATUS_LIST;
        $allowedStatusUpdate = Constants::ALLOWED_UPDATE_STATUS_LIST;
        $disallowedStatusUpdate = Constants::DISALLOWED_UPDATE_STATUS_LIST;

        if ($model->isAttributeChanged($attribute)) {
            if (!$model->isNewRecord) {
                if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && Yii::$app->coreAPI->superAdmin()) {
                    $model->addError($attribute, Yii::t('app', 'deletedStatusChanged', ['value' => $statusList[Constants::STATUS_DELETED]]));
                    return;
                }
            }
            
            if ($oldStatus !== null) {
                if (!isset($allowedStatusUpdate[$oldStatus])) {
                    $model->addError($attribute, Yii::t('app', 'invalidStatusTransition'));
                    return;
                }
            }
        }
    }

    /**
     * Validates model dependencies before allowing updates.
     * Prevents updates to fields when dependent records exist.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['status', 'name'], function($attribute, $params) {
     *             CoreModel::validateDependencies($attribute, $params, $this, [
     *                 ['className' => 'Order', 'field' => ['user_id']],
     *                 ['className' => 'Payment', 'field' => ['user_id']]
     *             ]);
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param string $attribute Attribute being validated
     * @param array $params Validation parameters
     * @param object $model Model being validated
     * @param array $dependencies Array of dependent models and their fields
     */
    public static function validateDependencies($attribute, $params, $model, array $dependencies): void
    {
        if (!$model->isNewRecord) {
            $changedAttributes = [];
            $fields = Yii::$app->params['dependenciesUpdate'][$model->tableName()];
            $disallowedStatusUpdate = Constants::DISALLOWED_UPDATE_STATUS_LIST;

            foreach ($fields as $field) {
                if ($model->isAttributeChanged($field)) {
                    $changedAttributes[] = $field;
                }

                if ($field === 'status' && in_array($model->status, $disallowedStatusUpdate)) {
                    $changedAttributes[] = $field;
                }
            }

            if (!empty($changedAttributes)) {
                $dataId = $model->id;

                foreach ($dependencies as $dependency) {
                    $className = 'app\models\\' . $dependency['className'];
                    
                    foreach ($dependency['field'] as $field) {
                        if ($className::find()->where([$field => $dataId])->exists()) {
                            foreach ($changedAttributes as $changedAttribute) {
                                $model->addError($changedAttribute, Yii::t('app', 'updatePermission', [
                                    'label' => $model->getAttributeLabel($changedAttribute),
                                    'tableName' => $model->tableName(),
                                ]));
                            }

                            return;
                        }
                    }
                }
            }
        }
    }

    /**
     * Validates dependencies for array fields.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['user_id'], function($attribute, $params) {
     *             CoreModel::validateDependenciesInArray($attribute, $params, $this, [
     *                 ['className' => 'Order', 'field' => ['user_id']],
     *                 ['className' => 'Payment', 'field' => ['user_id']]
     *             ]);
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param string $attribute Attribute being validated
     * @param array $params Validation parameters
     * @param object $model Model being validated
     * @param array $dependencies Array of dependent models and their fields
     */
    public static function validateDependenciesInArray($attribute, $params, $model, array $dependencies): void
    {
        if (!$model->isNewRecord) {
            $changedAttributes = [];
            $fields = Yii::$app->params['dependenciesUpdate'][$model->tableName()];
            $disallowedStatusUpdate = Constants::DISALLOWED_UPDATE_STATUS_LIST;

            foreach ($fields as $field) {
                if ($model->isAttributeChanged($field)) {
                    $changedAttributes[] = $field;
                }

                if ($field === 'status' && in_array($model->status, $disallowedStatusUpdate)) {
                    $changedAttributes[] = $field;
                }
            }

            if (!empty($changedAttributes)) {
                $dataId = $model->id;

                foreach ($dependencies as $dependency) {
                    $className = 'app\models\\' . $dependency['className'];
                    foreach ($dependency['field'] as $field) {
                        if ($className::find()->where("{$field} @> :dataId::jsonb", [':dataId' => $dataId])->exists()) {
                            foreach ($changedAttributes as $changedAttribute) {
                                $model->addError($changedAttribute, Yii::t('app', 'updatePermission', [
                                    'label' => $model->getAttributeLabel($changedAttribute),
                                    'tableName' => $model->tableName(),
                                ]));
                            }

                            return;
                        }
                    }
                }
            }
        }
    }

    /**
     * Sets up changelog filters for querying model history.
     * Handles both date range and user filters for audit logs.
     * 
     * Usage:
     * ```php
     * $query = ModelHistory::find();
     * $conditions = CoreModel::setChangelogFilters($searchModel, 
     *     ['created_at', 'updated_at'],
     *     ['created_by', 'updated_by']
     * );
     * $query->andWhere($conditions);
     * ```
     * 
     * @param object $model Model with changelog attributes
     * @param array $logDates Date fields to filter (default: created_at, updated_at, deleted_at)
     * @param array $logUsers User fields to filter (default: created_by, updated_by, deleted_by)
     * @return array Query conditions for changelog filtering
     */
    public static function setChangelogFilters($model, array $logDates = [], array $logUsers = []): array
    {
        $conditions = ['and'];
        
        if (!$logDates){
            $logDates = ['created_at', 'updated_at', 'deleted_at'];
        }

        if (!$logUsers){
            $logUsers = ['created_by', 'updated_by', 'deleted_by'];
        }

        foreach ($logDates as $logDate) {
            $dateValue = $model->{$logDate};

            if (!empty($dateValue)) {
                if (strpos($dateValue, ',') !== false) {
                    list($startDate, $endDate) = array_map('trim', explode(',', $dateValue));

                    $conditions[] = [
                        'and',
                        ['>=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $startDate],
                        ['<=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $endDate],
                    ];
                } else {
                    $conditions[] = ['=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $dateValue];
                }
            }
        }

        foreach ($logUsers as $logUser) {
            if (!empty($model->{$logUser})) {
                $conditions[] = ['ilike', new \yii\db\Expression("detail_info #>> '{change_log,$logUser}'"), $model->{$logUser}];
            }
        }

        return $conditions;
    }

    /**
     * Retrieves change log for a model.
     * Handles both insert and update operations.
     * 
     * Usage:
     * ```php
     * $model->detail_info['change_log'] = CoreModel::getChangeLog($model, $insert);
     * ```
     * 
     * @param object $model Model instance
     * @param bool $insert Whether this is an insert operation
     * @return array Change log array
     */
    public static function getChangeLog($model, bool $insert): array
    {
        $timestamp = Yii::$app->coreAPI->UTCTimestamp();
        $username = Yii::$app->coreAPI->getUsername();
        $changeLog = [];

        if ($insert) {
            $changeLog = [
                'created_at' => $timestamp,
                'created_by' => $username,
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ];
        } else {
            $changeLog = $model->detail_info['change_log'] ?? [];

            if (isset($model->status)) {
                if ($model->status === Constants::STATUS_DELETED) {
                    $changeLog['deleted_at'] = $timestamp;
                    $changeLog['deleted_by'] = $username;
                } else {
                    if ($model->getDirtyAttributes()) {
                        $changeLog['updated_at'] = $timestamp;
                        $changeLog['updated_by'] = $username;
                    }
                }
            }
        }

        return $changeLog;
    }

    /**
     * Validates required fields in a model attribute.
     * Checks for missing required fields and extra fields that shouldn't be present.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         ['details', function($attribute) {
     *             CoreModel::validateRequiredFields(
     *                 $this,
     *                 $attribute,
     *                 ['name', 'code', 'type']
     *             );
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute to validate
     * @param array $requiredFields List of required field names
     * @param array|null $item Optional specific item to validate instead of the attribute value
     * @return bool Returns false if validation passes, throws ErrorMessage on failure
     * @throws ErrorMessage when validation fails
     */
    public static function validateRequiredFields($model, $attribute, $requiredFields, $item = null): bool
    {
        $fields = $item ?? $model->$attribute;

        if (!is_array($fields)) {
            $model->addError($attribute, Yii::t('app', 'array', [
                'label' => $model->getAttributeLabel($attribute),
            ]));

            throw new ErrorMessage($model, Yii::t('app', 'validationFailed'), 422);
        }

        $extraFields = array_diff_key($fields, array_flip($requiredFields));
        if ($extraFields) {
            $model->addError($attribute, Yii::t('app', 'extraField', [
                'label' => $model->getAttributeLabel($attribute),
                'field' => implode(', ', array_keys($extraFields)),
                'value' => implode(', ', $requiredFields),
            ]));

            throw new ErrorMessage($model, Yii::t('app', 'extraFieldFound', [
                'label' => $model->getAttributeLabel($attribute),
            ]), 422);
        }

        $missingFields = array_diff($requiredFields, array_keys($fields));
        if ($missingFields) {
            $model->addError($attribute, Yii::t('app', 'missingField', [
                'field' => implode(', ', $missingFields),
            ]));

            throw new ErrorMessage($model, Yii::t('app', 'missingFieldFound', [
                'label' => $model->getAttributeLabel($attribute),
            ]), 422);
        }

        return false;
    }

    /**
     * Validates that no fields in an array are null.
     * Useful for ensuring all fields in a nested structure have values.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         ['details', function($attribute) {
     *             foreach ($this->details as $item) {
     *                 if (CoreModel::nullFieldValidator($this, $attribute, $item)) {
     *                     return;
     *                 }
     *             }
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute being validated
     * @param array $item Array of field values to check for null
     * @return bool True if any fields are null (validation fails), false otherwise
     */
    public static function nullFieldValidator($model, $attribute, $item): bool
    {
        $nullFields = array_keys(array_filter($item, function ($value) {
            return $value === null;
        }));
        if (!empty($nullFields)) {
            $model->addError($attribute, Yii::t('app', 'nullField', [
                'label' => $model->getAttributeLabel($attribute),
                'field' => implode(', ', $nullFields),
            ]));
            return true;
        }

        return false;
    }

    /**
     * Formats model validation errors into a standardized array format.
     * Converts Yii2's error format into a field-message pair array.
     * 
     * Usage:
     * ```php
     * if (!$model->validate()) {
     *     return [
     *         'errors' => CoreModel::getErrors($model->getErrors())
     *     ];
     * }
     * ```
     * 
     * @param array $errors Array of validation errors from model
     * @return array Array of formatted errors with field and message keys
     */
    public static function getErrors($errors = [])
    {
        $result = [];
        foreach ($errors as $attribute => $errorMessages) {
            foreach ($errorMessages as $errorMessage) {
                $result[] = [
                    'field' => $attribute,
                    'message' => $errorMessage,
                ];
            }
        }
        return $result;
    }

    /**
     * MongoDB string search with case-insensitive pattern matching.
     * Adds a regex-based search condition to the query.
     * 
     * Usage:
     * ```php
     * $where = [];
     * CoreModel::mdbStringLike('name', $searchTerm, $where);
     * // Results in: ['name' => ['$regex' => 'search.*term', '$options' => 'i']]
     * 
     * // With OR condition
     * CoreModel::mdbStringLike('name', $searchTerm, $where, 'or');
     * // Results in: [['name' => ['$regex' => 'search.*term', '$options' => 'i']]]
     * ```
     * 
     * @param string $field Field name to search in
     * @param string|null $value Search value
     * @param array &$where Reference to where conditions array
     * @param string $orWhere Optional 'or' for OR conditions
     */
    public static function mdbStringLike(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
    {
        if ($value !== null) {
            $query = [
                '$regex' => str_replace(' ', '.*', $value ?? ''),
                '$options' => 'i',
            ];
            
            match (strtolower($orWhere)) {
                'or' => $where[] = [$field => $query],
                default => $where[$field] = $query,
            };
        }
    }

    /**
     * MongoDB exact string match with case-insensitive comparison.
     * Adds an exact match condition using regex anchors.
     * 
     * Usage:
     * ```php
     * $where = [];
     * CoreModel::mdbStringEqual('code', 'ABC123', $where);
     * // Results in: ['code' => ['$regex' => '^ABC123$', '$options' => 'i']]
     * ```
     * 
     * @param string $field Field name to match
     * @param string|null $value Exact value to match
     * @param array &$where Reference to where conditions array
     * @param string $orWhere Optional 'or' for OR conditions
     */
    public static function mdbStringEqual(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
    {
        if ($value !== null) {
            $query = [
                '$regex' => '^' . $value . '$',
                '$options' => 'i',
            ];
            
            match (strtolower($orWhere)) {
                'or' => $where[] = [$field => $query],
                default => $where[$field] = $query,
            };
        }
    }

    /**
     * MongoDB number equality comparison.
     * Adds a numeric equality condition to the query.
     * 
     * Usage:
     * ```php
     * $where = [];
     * CoreModel::mdbNumberEqual('quantity', '100', $where);
     * // Results in: ['quantity' => 100]
     * ```
     * 
     * @param string $field Field name to compare
     * @param string|null $value Numeric value as string
     * @param array &$where Reference to where conditions array
     * @param string $orWhere Optional 'or' for OR conditions
     */
    public static function mdbNumberEqual(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
    {
        if ($value !== null) {
            $where[$field] = intval($value);
        }
    }

    /**
     * MongoDB multiple number comparison using $in operator.
     * Adds a condition to match multiple numeric values.
     * 
     * Usage:
     * ```php
     * $where = [];
     * CoreModel::mdbNumberMultiple('status', '1,2,3', $where);
     * // Results in: ['status' => ['$in' => [1, 2, 3]]]
     * ```
     * 
     * @param string $field Field name to compare
     * @param string|null $value Comma-separated numbers
     * @param array &$where Reference to where conditions array
     * @param string $orWhere Optional 'or' for OR conditions
     */
    public static function mdbNumberMultiple(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
    {
        if ($value !== null) {
            $value = array_map('intval', explode(',', $value)) ?? [];
            
            $query = [
                '$in' => $value,
            ];

            match (strtolower($orWhere)) {
                'or' => $where[] = [$field => $query],
                default => $where[$field] = $query,
            };
        }
    }

    /**
     * MongoDB status field query builder.
     * Adds conditions for status field excluding deleted records.
     * 
     * Usage:
     * ```php
     * $where = [];
     * CoreModel::mdbStatus('status', '1', $where);
     * // Results in: ['status' => ['$ne' => -1, '$eq' => 1]]
     * ```
     * 
     * @param string $field Status field name
     * @param string|null $value Status value to match
     * @param array &$where Reference to where conditions array
     * @param string $orWhere Optional 'or' for OR conditions
     */
    public static function mdbStatus(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
    {
        $query = [
            '$ne' => Constants::STATUS_DELETED,
        ];

        if ($value !== null) {
            $query['$eq'] = intval($value);
        }
        
        match (strtolower($orWhere)) {
            'or' => $where[] = [$field => $query],
            default => $where[$field] = $query,
        };
    }

    /**
     * Formats date to local timezone format.
     * Converts date string to application's local timezone format.
     * 
     * Usage:
     * ```php
     * $localDate = CoreModel::localDateFormatter('2023-01-01T00:00:00Z');
     * // Returns date in local timezone format from params
     * ```
     * 
     * @param string|null $date Date string to format
     * @return string|null Formatted date or null if input is null
     */
    public static function localDateFormatter($date)
    {
        if ($date === null) {
            return null;
        }
        
        return (new \DateTime($date))->format(Yii::$app->params['timestamp']['local']);
    }

    /**
     * Formats date to UTC timezone format.
     * Converts date string to UTC timezone format.
     * 
     * Usage:
     * ```php
     * $utcDate = CoreModel::utcDateFormatter('2023-01-01 07:00:00');
     * // Returns date in UTC format from params
     * ```
     * 
     * @param string|null $date Date string to format
     * @return string|null Formatted date or null if input is null
     */
    public static function utcDateFormatter($date)
    {
        if ($date === null) {
            return null;
        }

        return (new \DateTime($date))->format(Yii::$app->params['timestamp']['UTC']);
    }
}