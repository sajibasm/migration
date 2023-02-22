<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sync_table".
 *
 * @property int $id
 * @property int $sourceDb
 * @property int $destinationDb
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
 * @property int $status 0=Pull, 1=Schema_Sync, 2=Data_Sync, 9=Processed
 * @property string $createdAt
 * @property string $processedAt
 *
 * @property SyncHostDb $source
 * @property SyncHostDb $destination
 */
class SyncTable extends \yii\db\ActiveRecord
{
    const STATUS_PULL  = 0;
    const STATUS_SCHEMA_MIGRATION  = 1;
    const STATUS_DATA_MIGRATION  = 2;
    const STATUS_PROCESSED  = 9;

    const STATUS_LABEL = [
        self::STATUS_PULL=>'Pull',
        self::STATUS_SCHEMA_MIGRATION=>'Schema Sync...',
        self::STATUS_DATA_MIGRATION=>'Data Sync ...',
        self::STATUS_PROCESSED=>'Processed',
    ];
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
            [['sourceDb', 'destinationDb'], 'required'],
            [['sourceDb', 'destinationDb', 'isEngine', 'autoIncrement', 'isPrimary', 'isUnique', 'isIndex', 'cols', 'rows', 'isError', 'status'], 'integer'],
            [['primaryKeys', 'uniqueKeys', 'indexKeys', 'columnStatics', 'errorSummary'], 'string'],
            [['createdAt', 'processedAt'], 'safe'],
            [['tableName'], 'string', 'max' => 100],
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
            'sourceDb' => Yii::t('app', 'Source'),
            'destinationDb' => Yii::t('app', 'Destination'),
            'tableName' => Yii::t('app', 'Table'),
            'isEngine' => Yii::t('app', 'Engine'),
            'engineType' => Yii::t('app', 'Engine Type'),
            'autoIncrement' => Yii::t('app', 'AI'),
            'autoIncrementKey' => Yii::t('app', 'AI Key'),
            'isPrimary' => Yii::t('app', 'Primary'),
            'primaryKeys' => Yii::t('app', 'Primary Keys'),
            'isUnique' => Yii::t('app', 'Unique'),
            'uniqueKeys' => Yii::t('app', 'Unique Keys'),
            'isIndex' => Yii::t('app', 'Index'),
            'indexKeys' => Yii::t('app', 'Index Keys'),
            'maxColType' => Yii::t('app', 'MaxType'),
            'maxColValue' => Yii::t('app', 'MaxValue'),
            'cols' => Yii::t('app', 'Cols'),
            'rows' => Yii::t('app', 'Rows'),
            'columnStatics' => Yii::t('app', 'Statics'),
            'isError' => Yii::t('app', 'Error'),
            'errorSummary' => Yii::t('app', 'Summary'),
            'status' => Yii::t('app', 'Status'),
            'createdAt' => Yii::t('app', 'Created At'),
            'processedAt' => Yii::t('app', 'Processed At'),
        ];
    }
    public function getSource()
    {
        return $this->hasOne(SyncHostDb::className(), ['id' => 'sourceDb']);
    }

    public function getDestination()
    {
        return $this->hasOne(SyncHostDb::className(), ['id' => 'destinationDb']);
    }
}
