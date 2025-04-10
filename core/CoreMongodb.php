<?php

namespace app\core;

use Yii;
use app\helpers\Constants;
use yii\helpers\StringHelper;

class CoreMongodb 
{
    public static function getModelClassName($model): string
    {
		return StringHelper::basename(get_class($model));
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

	public static function mdbStringMatch(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		if ($value !== null) {
			$query = [
				'$elemMatch' => [
					'$regex' => '^' . $value . '$',
					'$options' => 'i',
				]
			];

			match (strtolower($orWhere)) {
				'or' => $where[] = [$field => $query],
				default => $where[$field] = $query,
			};
		}
	}
}