<?php

namespace app\components;
use app\models\SyncConfig;
use yii\db\Connection;

class DynamicConnection
{
    /**
     * @param $model
     * @param $dbname
     * @return Connection
     */
    public static function getConnection($model, $dbname): Connection
    {
        $dsn = SyncConfig::DB_TYPE[$model->dbType].":host=".$model->host .";dbname=".$dbname;
        return new Connection(['dsn' => $dsn, 'username' => $model->username, 'password' => $model->password]);
    }


    public static function getConnectionByModel($model)
    {
        try {
            $dsn = SyncConfig::DB_TYPE[$model->dbType].":host=".$model->host .";dbname=".$model->dbname;
            $connection = new Connection(['dsn' => $dsn, 'username' => $model->username, 'password' => $model->password,]);
             $connection->open();
             return  $connection;
        } catch (\yii\db\Exception $e) {
            echo "Connection Erros: ". $e->getMessage();
            return false;
        }
    }

    public static function getHostName($db)
    {

        if($db){
            return substr($db->dsn, (strpos($db->dsn, '=')+1), (strpos($db->dsn, ';')-strpos($db->dsn, '=')));
        }
        return false;
    }

}