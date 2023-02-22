<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "sync_host_db".
 *
 * @property int $id
 * @property string $host
 * @property string|null $dbname
 * @property int|null $type
 * @property string $createdAt
 * @property string $updatedAt
 */
class SyncHostDb extends \yii\db\ActiveRecord
{
    const TYPE = [
        1=>'Source',
        2=>'Target'
    ];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sync_host_db';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['host', 'dbname', 'type'], 'required'],
            [['type'], 'integer'],
            [['createdAt', 'updatedAt'], 'safe'],
            [['dbname'], 'unique', 'targetAttribute' => ['host', 'type', 'dbname'], 'message' => 'Combined configuration already exist.'],
            [['host', 'dbname'], 'string', 'max' => 100],
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
            'dbname' => Yii::t('app', 'Dbname'),
            'createdAt' => Yii::t('app', 'Created At'),
            'updatedAt' => Yii::t('app', 'Updated At'),
        ];
    }

    public static function getHostAndDb($type=1)
    {
        $models = self::find()->where(['type'=>$type])->all();
        return ArrayHelper::map($models, 'id', 'dbname');
    }

}
