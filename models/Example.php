<?php

namespace app\models;

use Yii;
use app\core\CoreModel;
use app\helpers\Constants;

/**
 * This is the model class for table "example".
 *
 * @property int $id
 * @property string $name
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 * @property int|null $sync 1: unsync, null: synced
 */
class Example extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'example';
    }

    /**
     * {@inheritdoc}
     * @return string the column name for optimistic locking of $lockVersion.
     */
    public function optimisticLock() {
        // return Constants::OPTIMISTIC_LOCK;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['name'], 'string', 'max' => 255],
                [['name'], 'required', 'on' => Constants::SCENARIO_CREATE],
                // [['name', 'status'], function ($attribute, $params, $validator) {
                //     CoreModel::validateDependencies($attribute, $params, $validator, $this, [
                //         ['className' => 'other_example', 'field' => ['example_id']],
                //     ]);
                // }, 'on' => Constants::SCENARIO_UPDATE_LIST],
            ], 
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'sync'];

        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
            'sync' => 'Sync',
        ];
    }

    /**
     * {@inheritdoc}
     * @return \app\models\query\ExampleQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\ExampleQuery(get_called_class());
    }

    /**
     * Returns an array of fields that should be returned by default by `toArray()` when no specific fields are specified.
     * @return array the list of fields that can be exported. Defaults to all attributes of the model.
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['sync']);

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->name = CoreModel::htmlPurifier($this->name);
            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {

        }
        
        parent::afterSave($insert, $changedAttributes);
    }
}
