<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sync_table".
 *
 * @property int $id
 * @property int $host
 * @property string $dbName
 * @property string $tableName
 * @property int $isEngine
 * @property string|null $engineType
 * @property int $autoIncrement
 * @property string|null $autoIncrementKey
 * @property int $isPrimary
 * @property string|null $primaryKeys
 * @property int $isUnique
 * @property string|null $uniqueKeys
 * @property int $isIndex
 * @property string|null $indexKeys
 * @property string|null $maxColType
 * @property string|null $maxColValue
 * @property int|null $cols
 * @property int|null $rows
 * @property string|null $columnStatics
 * @property int $isError
 * @property string|null $errorSummary
 * @property int $status
 * @property string $createdAt
 * @property string $processedAt
 */
class SyncTable extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sync_table';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['host', 'dbName', 'tableName'], 'required'],
            [['host', 'isEngine', 'autoIncrement', 'isPrimary', 'isUnique', 'isIndex', 'cols', 'rows', 'isError', 'status'], 'integer'],
            [['primaryKeys', 'uniqueKeys', 'indexKeys', 'columnStatics', 'errorSummary'], 'string'],
            [['createdAt', 'processedAt'], 'safe'],
            [['dbName', 'tableName'], 'string', 'max' => 100],
            [['engineType'], 'string', 'max' => 10],
            [['autoIncrementKey', 'maxColType'], 'string', 'max' => 20],
            [['maxColValue'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'host' => Yii::t('app', 'Host'),
            'dbName' => Yii::t('app', 'Db Name'),
            'tableName' => Yii::t('app', 'Table Name'),
            'isEngine' => Yii::t('app', 'Is Engine'),
            'engineType' => Yii::t('app', 'Engine Type'),
            'autoIncrement' => Yii::t('app', 'Auto Increment'),
            'autoIncrementKey' => Yii::t('app', 'Auto Increment Key'),
            'isPrimary' => Yii::t('app', 'Is Primary'),
            'primaryKeys' => Yii::t('app', 'Primary Keys'),
            'isUnique' => Yii::t('app', 'Is Unique'),
            'uniqueKeys' => Yii::t('app', 'Unique Keys'),
            'isIndex' => Yii::t('app', 'Is Index'),
            'indexKeys' => Yii::t('app', 'Index Keys'),
            'maxColType' => Yii::t('app', 'Max Col Type'),
            'maxColValue' => Yii::t('app', 'Max Col Value'),
            'cols' => Yii::t('app', 'Cols'),
            'rows' => Yii::t('app', 'Rows'),
            'columnStatics' => Yii::t('app', 'Column Statics'),
            'isError' => Yii::t('app', 'Is Error'),
            'errorSummary' => Yii::t('app', 'Error Summary'),
            'status' => Yii::t('app', 'Status'),
            'createdAt' => Yii::t('app', 'Created At'),
            'processedAt' => Yii::t('app', 'Processed At'),
        ];
    }
}
