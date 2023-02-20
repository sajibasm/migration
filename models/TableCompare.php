<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tableCompare".
 *
 * @property int $id
 * @property string $tableName
 * @property int $isEngine
 * @property string $engineType
 * @property int $autoIncrement
 * @property string $autoIncrementKey
 * @property int $isPrimary
 * @property string|null $primaryKeys
 * @property int $isUnique
 * @property string|null $uniqueKeys
 * @property int $isIndex
 * @property string|null $indexKeys
 * @property string|null $maxType
 * @property string $maxValue
 * @property int $cols
 * @property int $rows
 * @property string|null $columnStatics
 * @property int $isError
 * @property string|null $errorSummary
 * @property int $status 0=PULL
 1=Scheme
 2=Data
 4=Completed
 
 
 * @property string $createdAt
 * @property string $processedAt
 */
class TableCompare extends \yii\db\ActiveRecord
{
    const STATUS_PULL  = 0;
    const STATUS_SCHAMA_MIGRATION  = 1;
    const STATUS_DATA_MIGRATION  = 2;
    const STATUS_PROCESSED  = 9;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tableCompare';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tableName'], 'unique'],
            [['tableName', 'engineType', 'autoIncrement', 'maxValue'], 'required'],
            [['isEngine', 'autoIncrement', 'isPrimary', 'isUnique', 'isIndex', 'cols', 'rows', 'isError', 'status'], 'integer'],
            [['primaryKeys', 'uniqueKeys', 'indexKeys', 'columnStatics', 'errorSummary', 'createdAt', 'processedAt'], 'safe'],
            [['tableName'], 'string', 'max' => 255],
            [['engineType'], 'string', 'max' => 20],
            [['autoIncrementKey', 'maxType', 'maxValue'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
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
            'maxType' => Yii::t('app', 'Max'),
            'maxValue' => Yii::t('app', 'Max Value'),
            'cols' => Yii::t('app', 'Cols'),
            'rows' => Yii::t('app', 'Rows'),
            'columnStatics' => Yii::t('app', 'Statics'),
            'isError' => Yii::t('app', 'Error'),
            'errorSummary' => Yii::t('app', 'Summary'),
            'status' => Yii::t('app', 'Status'),
            'createdAt' => Yii::t('app', 'CreatedAt'),
            'processedAt' => Yii::t('app', 'ProcessedAt'),
        ];
    }
}
