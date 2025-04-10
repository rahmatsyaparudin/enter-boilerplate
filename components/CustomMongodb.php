<?php

namespace app\components;

use Yii;
use yii\db\Exception;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;

class CustomMongodb extends \MongoDB\Client
{
    public string $dsn;
    public string $database;
    public string $username;
    public string $password;

    public function upsert($model): void
    {
        try {
            $mongodb = new \MongoDB\Client($this->dsn);
            $collection = $mongodb->selectCollection($this->database, $model::tableName());
            $data = $model->toArray();
            $collection->updateOne(
                ['id' => $model->id],
                ['$set' => $data],
                ['upsert' => true, 'multi' => false]
            );
        } catch (\Exception $e) {
            Yii::$app->coreAPI->setMongodbSyncFailed($model);
        }
    }

    public function upsertMany($model, array $attrtibutes, $data): void
    {
        try {
            $mongodb = new \MongoDB\Client($this->dsn);
            $collection = $mongodb->selectCollection($this->database, $model::tableName());

            $bulkWrite = array_map(function ($item) use ($attrtibutes, $collection) {
                $filters = [];
                foreach ($attrtibutes as $attribute) {
                    $filters[$attribute] = intval($item[$attribute]);
                }

                return [
                    'updateOne' => [
                        $filters,
                        ['$set' => $item],
                        ['upsert' => true],
                    ],
                ];
            }, $data);

            $collection->bulkWrite($bulkWrite);
        } catch (\Exception $e) {
            Yii::$app->coreAPI->setMongodbSyncFailed($model);
        }
    }

    public function upsertManyCustomCollection($tableName, array $attrtibutes, $data): void
    {
        try {
            $mongodb = new \MongoDB\Client($this->dsn);
            $collection = $mongodb->selectCollection($this->database, $tableName);

            $bulkWrite = array_map(function ($item) use ($attrtibutes, $collection) {
                $filters = [];
                foreach ($attrtibutes as $attribute) {
                    $filters[$attribute] = intval($item[$attribute]);
                }

                return [
                    'updateOne' => [
                        $filters,
                        ['$set' => $item],
                        ['upsert' => true],
                    ],
                ];
            }, $data);

            $collection->bulkWrite($bulkWrite);
        } catch (\Exception $e) {
            // TODO: Handle Exception if custom table is failed to sync
            // Yii::$app->coreAPI->setMongodbSyncFailed($model);
        }
    }

    public function search($model, $filters, $projection = []): ActiveDataProvider
    {
        try {
            $sortOrder = $model->sort_dir === 'asc' ? 1 : -1;
            $sortBy = $model->sort_by ?? 'id';
            $page = $model->page ?? 1;
            $pageSize = (int) ($model->page_size ?: Yii::$app->params['pagination']['pageSize']);

            $client = new \MongoDB\Client($this->dsn);
            $collection = $client->selectCollection($this->database, 'inventory_product_list');
            $andFilters = [];

            if (isset($filters['where']) && !empty($filters['where'])) {
                $andFilters[] = $filters['where'];
            }

            if (isset($filters['orWhere']) && !empty($filters['orWhere'])) {
                $andFilters[] = [
                    '$or' => $filters['orWhere'],
                ];
            }

            $queryFilter = empty($andFilters) ? [] : [
                '$and' => $andFilters,
            ];

            $totalCount = $collection->countDocuments($queryFilter);

            $pageSize = min($totalCount, $pageSize);

            $pagination = new \yii\data\Pagination(['totalCount' => $totalCount]);
            $pagination->page = $page - 1;
            $pagination->pageSize = $pageSize;

            $options = [
                'limit' => $pagination->limit,
                'skip' => $pagination->offset,
                'sort' => [$sortBy => $sortOrder],
                'projection' => array_merge(
                    ['_id' => false],
                    $projection,
                ),
            ];

            $cursor = $collection->find($queryFilter, $options);
            $documents  = iterator_to_array($cursor);

            $dataProvider = new ActiveDataProvider([
                'models' => $documents,
                'pagination' => $pagination,
            ]);
            $dataProvider->totalCount = $totalCount;

            return $dataProvider;
        } catch (\Exception $e) {
            return new ActiveDataProvider([
                'models' => [],
                'pagination' => new \yii\data\Pagination(),
            ]);
        }
    }
}