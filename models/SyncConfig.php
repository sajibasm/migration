<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sync_config".
 *
 * @property int $id
 * @property int|null $dbType 1=mysql,2=mssql,3=oracle
 * @property int|null $type 1=Source,2=Destination
 * @property string $host
 * @property string|null $dbname
 * @property string $username
 * @property string $password
 * @property string $charset
 * @property int $status
 * @property string $createdAt
 * @property string $updatedAt
 */
class SyncConfig extends \yii\db\ActiveRecord
{
    const TYPE_SOURCE = 1;
    const TYPE_DESTINATION = 2;

    const DB_TYPE = [
        1 => 'mysql',
        2 => 'PostgreSQL',
        3 => 'mssql',
        4 => 'oracle',
    ];
    const TYPE = [
        self::TYPE_SOURCE => 'Source',
        self::TYPE_DESTINATION => 'Target',
    ];
    const STATUS = [
        0 => 'Inactive',
        1 => 'Active'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sync_config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['dbType', 'type', 'host', 'username', 'password'], 'required'],
            [['dbType'], 'unique', 'targetAttribute' => ['dbType', 'type', 'host'], 'message' => 'Combined configuration already exist.'],
            [['dbType', 'type', 'status'], 'integer'],
            [['createdAt', 'updatedAt'], 'safe'],
            [['host', 'dbname', 'password', 'charset'], 'string', 'max' => 100],
            [['username'], 'string', 'max' => 50],
            ['host', 'checkConnection'],
        ];
    }

    public function checkConnection($attribute, $params)
    {
        if (!empty($this->dbType) && !empty($this->host) && !empty($this->username) && !empty($this->password) ) {
            try {
                $dbType = SyncConfig::DB_TYPE[$this->dbType];
                $host = $this->host;
                $dbName = $this->dbname;
                $dsn = "${dbType}:host=${host};dbname=${dbName}";
                $connection = new \yii\db\Connection([
                    'dsn' => $dsn,
                    'username' => $this->username,
                    'password' => $this->password,
                ]);
                $connection->open();
            } catch (\yii\db\Exception $e) {
                $this->addError($attribute, Yii::t('app', $e->getMessage()));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'dbType' => Yii::t('app', 'DB Type'),
            'type' => Yii::t('app', 'Type'),
            'host' => Yii::t('app', 'Host'),
            'dbname' => Yii::t('app', 'Database Name'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'charset' => Yii::t('app', 'Charset'),
            'status' => Yii::t('app', 'Status'),
            'createdAt' => Yii::t('app', 'Created At'),
            'updatedAt' => Yii::t('app', 'Updated At'),
        ];
    }
}
