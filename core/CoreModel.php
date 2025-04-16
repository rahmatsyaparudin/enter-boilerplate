<?php

namespace app\core;

use Yii;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use yii\helpers\StringHelper;
use app\exceptions\ErrorMessage;

class CoreModel 
{
    public static function getModelClassName($model): string
    {
		return StringHelper::basename(get_class($model));
	}

	public static function nullSafe(?string $value = null): ?string
	{
		return (is_string($value) && strtolower($value) === 'null') || $value === '' ? null : $value;
	}

	public static function isNullString($value): bool
	{
		return (is_null($value) || (is_string($value) && strtolower($value) === 'null'));
	}

	public static function htmlPurifier(?string $value): ?string
	{
		return $value === null ? null : self::nullSafe(strip_tags(HtmlPurifier::process($value)));
	}

	public static function contentPurifier(?string $value): ?string
	{
		return $value === null ? null : self::nullSafe(HtmlPurifier::process($value));
	}

	public static function purifyArray($array)
	{
		return is_array($array) ? array_map([__CLASS__, 'purifyObject'], $array) : null;
	}
	
	public static function purifyObject($object)
	{
		return is_null($object) ? null : array_map([__CLASS__, 'htmlPurifier'], $object);
	}

    public static function setLikeFilter(?string $value = null, ?string $field = 'name'): array
	{
		return ['ilike', $field, $value ? '%' . str_replace(' ', '%', trim($value)) . '%' : null, false];
	}

    public static function isRestrictedStatus(int $status): bool
    {
        return in_array($status, Constants::RESTRICT_STATUS_LIST, true);
    }

	public static function isJsonString($value)
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

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

	public static function validateAttributeArray($model, $attribute, $label)
    {
        if (!is_array($model->$attribute)) {
            if (self::isNullString($model->$attribute)) {
				return;
			}

			$model->addError($attribute, Yii::t('app', 'array', ['label' => $label]));
			return;
        }
    }

	public static function validateAttributeArrayOrNull($model, $attribute, $label)
    {
        if (!is_array($model->$attribute)) {
            if (self::isNullString($model->$attribute)) {
				return;
			}

			$model->addError($attribute, Yii::t('app', 'array', ['label' => $label]));
			return;
        }
    }

	public static function getSyncRules($model=null): array
    {
        return [
			[['sync'], 'default', 'value' => null],
            [['sync'], 'integer'],
        ];
    }

	public static function getLockVersionRules($model=null, $requiredOn=null): array
    {
        return [
            [[Constants::OPTIMISTIC_LOCK], 'required', 'on' => $requiredOn ?? Constants::SCENARIO_UPDATE_LIST],
            [[Constants::OPTIMISTIC_LOCK], 'integer'],
            [[Constants::OPTIMISTIC_LOCK], 'default', 'value' => 1, 'on' => [Constants::SCENARIO_CREATE]],
        ];
    }

	public static function getLockVersionRulesOnly(): array
    {
        return [
            [[Constants::OPTIMISTIC_LOCK], 'integer'],
        ];
    }

	public static function getPaginationRules($model): array
    {
        return [
            [['sort_dir', 'sort_by'], 'string'],
            [['page', 'page_size'], 'integer'],
            [['page'], function ($attribute, $params) use ($model) {
				if ($model->$attribute <= 0) {
					$model->addError($attribute, Yii::t('app', 'pageMustBeGreaterThanZero'));
				}
            }],
        ];
    }

	public static function setPagination(?array $params, $dataProvider): array {
		$pageSize = min((int)$dataProvider->getTotalCount(), (int)($params['page_size'] ?? Yii::$app->params['pagination']['pageSize']));

		return [
			'page' => (int)($params['page'] ?? 1) - 1,
			'pageSize' => $pageSize,
			'defaultPageSize' => $pageSize,
		];
	}

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

    public static function validateStatusUpdate(string $attribute, ?array $params, $model): void
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
					
				if (in_array($oldStatus, $disallowedStatusUpdate)) {
					$model->addError($attribute, Yii::t('app', 'disallowedStatusUpdate', ['value' => $statusList[$oldStatus]]));
					return;
				}
	
				if (!in_array($newStatus, $allowedStatusUpdate[$oldStatus])) {
					if ($model->hasErrors($attribute)) {
						$model->addError($attribute, Yii::t('app', 'cannotChangeStatus', [
							'value' => $statusList[$oldStatus],
							'newValue' => $statusList[$newStatus]
						]));
						return;
					}
				}
			}
        }
    }

    public static function validateDependencies($attribute, $params, $validator, $model, array $dependencies): void
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

	public static function validateRequiredFields($model, $attribute, $requiredFields, $item = null): bool
	{
		$fields = $item ?? $model->$attribute;

		if (!is_array($fields)) {
			$model->addError($attribute, Yii::t('app', 'array', [
				'label' => $model->getAttributeLabel($attribute),
			]));

			throw new ErrorMessage($model, Yii::t('app', 'array', [
				'label' => $model->getAttributeLabel($attribute),
			]), 422);
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

	public static function nullFieldValidator($model, $attribute, $item): bool {
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
	 * Creates a MongoDB regex filter for a like query.
	 *
	 * @param string|null $value the value to search for
	 * @return array the MongoDB regex filter
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

	public static function mdbNumberEqual(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		if ($value !== null) {
			$where[$field] = intval($value);
		}
	}

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

	public static function localDateFormatter($date)
	{
		if ($date === null) {
			return null;
		}
		
		return (new \DateTime($date))->format(Yii::$app->params['timestamp']['local']);
	}

	public static function utcDateFormatter($date)
	{
		if ($date === null) {
			return null;
		}

		return (new \DateTime($date))->format(Yii::$app->params['timestamp']['UTC']);
	}
}