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
 * @property int $autoIncrement
 * @property int $isPrimary
 * @property int $isForeign
 * @property int $isUnique
 * @property int $isIndex
 * @property int|null $isCols
 * @property int|null $isRows
 * @property string|null $extra
 * @property int $isSuccess
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
    const STATUS_TABLE_META_QUEUE  = 0;
    const STATUS_TABLE_META_COMPLETED  = 1;
    const STATUS_SCHEMA_QUEUE  = 2;
    const STATUS_SCHEMA_COMPLETED  = 3;
    const STATUS_DATA_QUEUE  = 4;
    const STATUS_DATA_COMPLETED  = 5;
    const STATUS_PROCESSED  = 9;

    const STATUS_LABEL = [
        self::STATUS_TABLE_META_QUEUE=>'TableMetaQueue',
        self::STATUS_TABLE_META_COMPLETED=>'TableMetaCompleted',
        self::STATUS_SCHEMA_QUEUE=>'SchemaQueue',
        self::STATUS_SCHEMA_COMPLETED=>'SchemaCompleted',
        self::STATUS_DATA_QUEUE=>'DataQueue',
        self::STATUS_DATA_COMPLETED=>'DataCompleted',
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
            [['sourceDb', 'destinationDb', 'tableName'], 'required'],
            [['tableName'], 'unique', 'targetAttribute' => ['tableName', 'destinationDb', 'sourceDb'], 'message' => 'Combined configuration already exist.'],
            [['sourceDb', 'destinationDb', 'isEngine', 'autoIncrement', 'isPrimary', 'isForeign', 'isUnique', 'isIndex', 'isCols', 'isRows',  'isSuccess', 'status'], 'integer'],
            [['extra', 'errorSummary'], 'string'],
            [['createdAt', 'processedAt'], 'safe'],
            [['tableName'], 'string', 'max' => 100],
            [['engine'], 'string', 'max' => 20],
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
            'autoIncrement' => Yii::t('app', 'AI'),
            'isPrimary' => Yii::t('app', 'PRI'),
            'isForeign' => Yii::t('app', 'FRN'),
            'isUnique' => Yii::t('app', 'UNI'),
            'isIndex' => Yii::t('app', 'IND'),
            'isCols' => Yii::t('app', 'Col'),
            'isRows' => Yii::t('app', 'Row'),
            'extra' => Yii::t('app', 'Extra'),
            'isSuccess' => Yii::t('app', 'Success'),
            'errorSummary' => Yii::t('app', 'Errors'),
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
        $timeDiffernce = $today-$str;

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

        if(intval($timeDiffernce/$years) > 1)
        {
            return intval($timeDiffernce/$years)." years ago";
        }else if(intval($timeDiffernce/$years) > 0)
        {
            return intval($timeDiffernce/$years)." year ago";
        }else if(intval($timeDiffernce/$months) > 1)
        {
            return intval($timeDiffernce/$months)." months ago";
        }else if(intval(($timeDiffernce/$months)) > 0)
        {
            return intval(($timeDiffernce/$months))." month ago";
        }else if(intval(($timeDiffernce/$days)) > 1)
        {
            return intval(($timeDiffernce/$days))." days ago";
        }else if (intval(($timeDiffernce/$days)) > 0)
        {
            return intval(($timeDiffernce/$days))." day ago";
        }else if (intval(($timeDiffernce/$hours)) > 1)
        {
            return intval(($timeDiffernce/$hours))." hours ago";
        }else if (intval(($timeDiffernce/$hours)) > 0)
        {
            return intval(($timeDiffernce/$hours))." hour ago";
        }else if (intval(($timeDiffernce/$minutes)) > 1)
        {
            return intval(($timeDiffernce/$minutes))." minutes ago";
        }else if (intval(($timeDiffernce/$minutes)) > 0)
        {
            return intval(($timeDiffernce/$minutes))." minute ago";
        }else if (intval(($timeDiffernce)) > 1)
        {
            return intval(($timeDiffernce))." seconds ago";
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
