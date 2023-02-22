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

   protected function getShortTime($created_time)
    {
        //date_default_timezone_set('Asia/Dhaka'); //Change as per your default time
        $str = strtotime($created_time);
        $today = strtotime(date('Y-m-d H:i:s'));

        // It returns the time difference in Seconds...
        $time_differnce = $today-$str;

        // To Calculate the time difference in Years...
        $years = 60*60*24*365;

        // To Calculate the time difference in Months...
        $months = 60*60*24*30;

        // To Calculate the time difference in Days...
        $days = 60*60*24;

        // To Calculate the time difference in Hours...
        $hours = 60*60;

        // To Calculate the time difference in Minutes...
        $minutes = 60;

        if(intval($time_differnce/$years) > 1)
        {
            return intval($time_differnce/$years)." years ago";
        }else if(intval($time_differnce/$years) > 0)
        {
            return intval($time_differnce/$years)." year ago";
        }else if(intval($time_differnce/$months) > 1)
        {
            return intval($time_differnce/$months)." months ago";
        }else if(intval(($time_differnce/$months)) > 0)
        {
            return intval(($time_differnce/$months))." month ago";
        }else if(intval(($time_differnce/$days)) > 1)
        {
            return intval(($time_differnce/$days))." days ago";
        }else if (intval(($time_differnce/$days)) > 0)
        {
            return intval(($time_differnce/$days))." day ago";
        }else if (intval(($time_differnce/$hours)) > 1)
        {
            return intval(($time_differnce/$hours))." hours ago";
        }else if (intval(($time_differnce/$hours)) > 0)
        {
            return intval(($time_differnce/$hours))." hour ago";
        }else if (intval(($time_differnce/$minutes)) > 1)
        {
            return intval(($time_differnce/$minutes))." minutes ago";
        }else if (intval(($time_differnce/$minutes)) > 0)
        {
            return intval(($time_differnce/$minutes))." minute ago";
        }else if (intval(($time_differnce)) > 1)
        {
            return intval(($time_differnce))." seconds ago";
        }else
        {
            return "few seconds ago";
        }
    }

    public function afterFind()
    {
        $this->createdAt  = $this->getShortTime($this->createdAt);
        parent::afterFind(); // TODO: Change the autogenerated stub
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
