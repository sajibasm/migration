<?php

namespace app\components;

use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use Exception;
use Yii;
use yii\db\Connection;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;


class SyncUtility
{

    public static function getHostToDatabase($id)
    {
        try {
            $newRecords = 0;
            $model = SyncConfig::findOne(['id' => $id]);
            $db = DynamicConnection::getConnectionByModel($model);
            $database = $db->createCommand("SHOW DATABASES;")->queryAll();
            $ignoreDatabase = ['information_schema', 'innodb', 'mysql', 'performance_schema', 'sys', 'tmp'];
            $syncHostDB = new SyncHostDb();
            foreach ($database as $name) {
                if (!in_array($name['Database'], $ignoreDatabase)) {
                    $syncHostDB->config = $model->id;
                    $syncHostDB->host = $model->host;
                    $syncHostDB->dbname = $name['Database'];
                    $syncHostDB->type = $model->type;
                    if ($syncHostDB->save()) {
                        unset($syncHostDB->id);
                        $syncHostDB->isNewRecord = true;
                        $newRecords++;
                    }
                }
            }
            return $newRecords ?: false;
        } catch (\Exception  $e) {
            dd($e);
        }
    }


    public static function getTableInfo(Connection $connection, string $database, $table, $clearCache = false)
    {
        if ($clearCache) {
            return Yii::$app->getCache()->flush();
        }

        $key = md5("getTableInfo" . $table . $connection->dsn);
        $infoSchemaData = Yii::$app->getCache()->get($key);
        if ($infoSchemaData === false) {
            $schemaData = $connection->createCommand("SELECT * FROM  information_schema.TABLES WHERE  TABLE_SCHEMA = '${database}';")->queryAll();
            foreach ($schemaData as $row) {
                if ($row['TABLE_NAME'] === $table) {
                    $infoSchemaData = $row;
                }
                Yii::$app->getCache()->set(md5("getTableInfo" . $row['TABLE_NAME'] . $connection->dsn), $row, 180);
            }
        }
        return $infoSchemaData;
    }


    public static function saveTableMetaQueue(SyncHostDb $source, SyncHostDb $target)
    {
        try {
            $rows = [];
            $sourceConnection = DynamicConnection::getConnection($source->configuration, $source->dbname);

            foreach ($sourceConnection->schema->getTableNames() as $key => $tableName) {
                $rows[] = [
                    null,
                    $source->id,
                    $target->id,
                    $tableName,
                    false,
                    false,
                    false,
                    false,
                    false,
                    false,
                    false,
                    false,
                    Json::encode([]),
                    false,
                    Json::encode([]),
                    SyncTable::STATUS_TABLE_META_QUEUE,
                    date('Y-m-d h:i:s'),
                    date('Y-m-d h:i:s')
                ];
            }

            return Yii::$app->db->createCommand()->batchInsert(SyncTable::tableName(), [
                'id', 'sourceId', 'targetID', 'tableName', 'isEngine', 'autoIncrement',
                'isPrimary', 'isForeign', 'isUnique', 'isIndex', 'isCols', 'isRows', 'extra',
                'isSuccess', 'errorSummary', 'status', 'createdAt', 'processedAt'
            ], $rows)->execute();

        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    private static function singularSync(SyncTable &$syncModel, Connection $sourceConnection, Connection $targetConnection, TableSchema $sourceSchema, TableSchema $targetSchema, $sourceSchemaInfo, $targetSchemaInfo)
    {

        $errorSummary = [];
        $syncModel->isEngine = 1;
        $syncModel->autoIncrement = 1;
        $syncModel->isPrimary = 1;
        $syncModel->isForeign = 1;
        $syncModel->isUnique = 1;
        $syncModel->isIndex = 1;
        $syncModel->isCols = 1;
        $syncModel->isRows = 1;
        $syncModel->isSuccess = 1;
        $syncModel->status = SyncTable::STATUS_SCHEMA_COMPLETED;
        $syncModel->extra = Json::encode(['schema' => $sourceSchema, 'info' => $sourceSchemaInfo]);
        $syncModel->tableName = $sourceSchema->fullName;

        //Check Engine type
        if (ArrayHelper::getValue($sourceSchemaInfo, 'ENGINE')) {
            if (ArrayHelper::getValue($targetSchemaInfo, 'ENGINE')) {
                if ((ArrayHelper::getValue($sourceSchemaInfo, 'ENGINE') !== ArrayHelper::getValue($targetSchemaInfo, 'ENGINE'))) {
                    $errorSummary[] = "<b>Engine</b> (" . $syncModel->tableName . ") doesn't match ";
                    $syncModel->isEngine = 0;
                    $syncModel->isSuccess = 0;
                }
            }
        }

        //find Auto Increment
        if (ArrayHelper::getValue($sourceSchema, 'columns')) {
            $isEmptyAutoIncrement = [];
            $autoIncrementId = '';
            foreach (ArrayHelper::getValue($sourceSchema, 'columns') as $sourceColumn) {
                if ($sourceColumn->autoIncrement) {
                    foreach (ArrayHelper::getValue($targetSchema, 'columns') as $targetColumn) {
                        if ($targetColumn->autoIncrement && $sourceColumn->name === $targetColumn->name) {
                            $isEmptyAutoIncrement = false;
                            $autoIncrementId = $sourceColumn->name;
                        }
                    }
                }else{
                    $isEmptyAutoIncrement = false;
                }
            }
            if ($isEmptyAutoIncrement) {
                $errorSummary[] = "<b>Auto Increment</b> (" . $autoIncrementId . ") doesn't set.";
                $syncModel->autoIncrement = 0;
                $syncModel->isSuccess = 0;
            }
        }

        //Check if primary key is missing.
        if (ArrayHelper::getValue($sourceSchema, 'primaryKey') && count(ArrayHelper::getValue($sourceSchema, 'primaryKey')) > 0) {
            $emptyPrimaryKeys = [];
            if (ArrayHelper::getValue($targetSchema, 'primaryKey')) {
                foreach (ArrayHelper::getValue($sourceSchema, 'primaryKey') as $sourcePrimary) {


                    $isMatch = false;
                    foreach (ArrayHelper::getValue($targetSchema, 'primaryKey') as $targetPrimary) {
                        if ($sourcePrimary === $targetPrimary) { $isMatch = true; }
                    }
                    if(!$isMatch){
                        $emptyPrimaryKeys[] = $sourcePrimary;
                    }
                }
            } else {
                $emptyPrimaryKeys = ArrayHelper::getValue($sourceSchema, 'primaryKey');
            }

            if ($emptyPrimaryKeys) {
                $errorSummary[] = "<b>Primary Key</b> doesn't set (" . implode(", ", $emptyPrimaryKeys) . ")";
                $syncModel->isPrimary = 0;
                $syncModel->isSuccess = 0;
            }
        }

        //Check if unique column is missing.
        $sourceUniqueColumns = $sourceConnection->schema->findUniqueIndexes($sourceSchema);
        $targetUniqueColumns = $targetConnection->schema->findUniqueIndexes($sourceSchema);
        if ($sourceUniqueColumns) {
            $emptyUniqueKeys = [];
            foreach ($sourceUniqueColumns as $sourceUniqueColumn) {
                if ($targetUniqueColumns) {
                    $match = false;
                    foreach ($targetUniqueColumns as $targetUniqueColumn) {
                        if ($targetUniqueColumn[0] === $sourceUniqueColumn[0]) {
                            $match = true;
                        }
                    }
                    if (!$match) {
                        $emptyUniqueKeys[] = $sourceUniqueColumn[0];
                    }
                } else {
                    $emptyUniqueKeys[] = $sourceUniqueColumn[0];
                }
            }

            if ($emptyUniqueKeys) {
                $errorSummary[] = "<b>Unique Key</b> (" . implode(", ", $emptyUniqueKeys) . ") doesn't set.";
                $syncModel->isUnique = 0;
                $syncModel->isSuccess = 0;
            }
        }


        //Check if foreign key is missing.
        if (ArrayHelper::getValue($sourceSchema, 'foreignKeys') && count(ArrayHelper::getValue($sourceSchema, 'foreignKeys')) > 0) {
            $emptyForeignKeys = [];

            foreach (ArrayHelper::getValue($sourceSchema, 'foreignKeys') as $sourceForeignKeyName => $sourceForeignKey) {
                if(ArrayHelper::getValue($targetSchema, 'foreignKeys')){
                    $isMatch = false;
                    foreach (ArrayHelper::getValue($targetSchema, 'foreignKeys') as $targetForeignKeyName => $targetForeignKey) {
                        if ($sourceForeignKey[0] === $targetForeignKey[0]) { $isMatch = true;}
                    }
                    if(!$isMatch){
                        $emptyForeignKeys[] = $sourceForeignKey[0];
                    }
                }else{
                    $emptyForeignKeys[] = $sourceForeignKey[0];
                }
            }

            if ($emptyForeignKeys) {
                $errorSummary[] = "<b>Foreign Key</b> (" . implode(", ", $emptyForeignKeys) . ") doesn't set";
                $syncModel->isForeign = 0;
                $syncModel->isSuccess = 0;
            }
        }

        // compare and find the missing columns with attributes
        if (ArrayHelper::getValue($sourceSchema, 'columns')) {
            foreach (ArrayHelper::getValue($sourceSchema, 'columns') as $sourceColumn) {
                $columnCompare = [];
                $columnMatch = false;
                foreach (ArrayHelper::getValue($targetSchema, 'columns') as $targetColumn) {
                    if ($sourceColumn->name === $targetColumn->name) {
                        $columnMatch = true;
                        $columnCompare = array_diff((array)$sourceColumn, (array)$targetColumn);
                    }
                }

                if (!$columnMatch) {
                    $errorSummary[] = "<b>Columns</b> (" . $sourceColumn->name . ") doesn't set.";
                    $syncModel->isCols = 0;
                    $syncModel->isSuccess = 0;
                }

                if ($columnCompare) {
                    //For Column Comments missing from source to target
                    if (ArrayHelper::getValue($columnCompare, 'comment')) {
                        $errorSummary[] = "<b>" . $sourceColumn->name . "</b> (" . ArrayHelper::getValue($columnCompare, 'comment') . ") comment doesn't set.";
                        $syncModel->isCols = 0;
                        $syncModel->isSuccess = 0;
                    }
                    if (ArrayHelper::getValue($columnCompare, 'dbType')) {
                        $errorSummary[] = "<b>" . $sourceColumn->name . "</b> (" . ArrayHelper::getValue($columnCompare, 'dbType') . ") doesn't matched.";
                        $syncModel->isCols = 0;
                        $syncModel->isSuccess = 0;
                    }
                }
            }
        }

//        if ($sourceSchema->fullName === 'role') {
//            dd($errorSummary);
//            dd($sourceSchema);
//            dd($targetSchema);
//            //die();
//        }


        $syncModel->errorSummary = Json::encode($errorSummary);
        if (!$syncModel->save()) {
            dd($syncModel->getErrors());
        }


//        if (ArrayHelper::getValue($sourceTable, 'extra.column.total') !== ArrayHelper::getValue($targetTable, 'extra.column.total')) {
//            $syncObject->setCol(true);
//            $syncObject->setNumberOfCols(ArrayHelper::getValue($sourceTable, 'extra.column.total'));
//            $syncObject->setError(true);
//            $syncObject->setErrorSummary("<b>Columns</b> doesn't match, original ( " . ArrayHelper::getValue($sourceTable, 'extra.column.total') . " ) Diff: ( " . (ArrayHelper::getValue($sourceTable, 'extra.column.total') - ArrayHelper::getValue($targetTable, 'extra.column.total')) . " )");
//
//            $missingCol = [];
//            $sourceColInfos = ArrayHelper::getValue($sourceTable, 'extra.column.info');
//            $targetColInfos = ArrayHelper::getValue($targetTable, 'extra.column.info');
//            foreach ($sourceColInfos as $sourceColInfo) {
//                $colName = trim($sourceColInfo['COLUMN_NAME']); // country_code
//                $isColMatch = false;
//                foreach ($targetColInfos as $targetColInfo) {
//                    if (trim($sourceColInfo['COLUMN_NAME']) === trim($targetColInfo['COLUMN_NAME'])) {
//                        $isColMatch = true;
//                    }
//                }
//                if (!$isColMatch) {
//                    $missingCol[] = $colName;
//                }
//            }
//            $syncObject->setErrorSummary("<b> - Absent </b> ( " . implode(", ", $missingCol) . " )");
//        }
//
//        if (ArrayHelper::getValue($sourceTable, 'extra.row.total') !== ArrayHelper::getValue($targetTable, 'extra.row.total')) {
//            $syncObject->setRows(true);
//            $syncObject->setNumberOfRows(ArrayHelper::getValue($sourceTable, 'extra.row.total'));
//            $syncObject->setError(true);
//            $syncObject->setErrorSummary("<b>Rows</b> doesn't match, original ( " . ArrayHelper::getValue($sourceTable, 'extra.row.total') . " ) Diff: ( " . (ArrayHelper::getValue($sourceTable, 'extra.row.total') - ArrayHelper::getValue($targetTable, 'extra.row.total')) . " )");
//        }
//
//        $sourceExtraInfos = ArrayHelper::getValue($sourceTable, 'extra.column.info');
//        $targetExtraInfos = ArrayHelper::getValue($targetTable, 'extra.column.info');
//        if ($sourceExtraInfos && $targetExtraInfos) {
//            foreach ($sourceExtraInfos as $sourceExtraInfo) {
//                $colName = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_NAME'); // country_code
//                $colDefault = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_DEFAULT');  //
//                $colIsNullable = ArrayHelper::getValue($sourceExtraInfo, 'IS_NULLABLE');  //
//                $colDataType = ArrayHelper::getValue($sourceExtraInfo, 'DATA_TYPE'); // varchar, char, int, 'timestamp', '
//                $colKey = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_KEY');  // PRI, UNI, MUL, ''
//                $colCharMaxLength = ArrayHelper::getValue($sourceExtraInfo, 'CHARACTER_MAXIMUM_LENGTH'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION
//                $colNumberPrecision = ArrayHelper::getValue($sourceExtraInfo, 'NUMERIC_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
//                $colDatetimePrecision = ArrayHelper::getValue($sourceExtraInfo, 'DATETIME_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
//                $colType = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_TYPE');  // int, varchar , 'CURRENT_TIMESTAMP'
//                $colComment = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_COMMENT');  //
//                $colExtra = ArrayHelper::getValue($sourceExtraInfo, 'EXTRA');  // 1, 'Running', 'CURRENT_TIMESTAMP'
//                $colCollationName = ArrayHelper::getValue($sourceExtraInfo, 'COLLATION_NAME');  // 1, 'Running', 'CURRENT_TIMESTAMP'
//
//                foreach ($targetExtraInfos as $targetExtraInfo) {
//                    $destColName = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_NAME'); // country_code
//                    $destColDefault = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_DEFAULT');  //
//                    $destColIsNullable = ArrayHelper::getValue($targetExtraInfo, 'IS_NULLABLE');  //
//                    $destColDataType = ArrayHelper::getValue($targetExtraInfo, 'DATA_TYPE'); // varchar, char, int, 'timestamp', '
//                    $destColKey = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_KEY');  // PRI, UNI, MUL, ''
//                    $destColCharMaxLength = ArrayHelper::getValue($targetExtraInfo, 'CHARACTER_MAXIMUM_LENGTH'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION
//                    $destColNumberPrecision = ArrayHelper::getValue($targetExtraInfo, 'NUMERIC_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
//                    $destColDatetimePrecision = ArrayHelper::getValue($targetExtraInfo, 'DATETIME_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
//                    $destColType = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_TYPE');  // int, varchar , 'CURRENT_TIMESTAMP'
//                    $destColComment = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_COMMENT');  //
//                    $destColExtra = ArrayHelper::getValue($targetExtraInfo, 'EXTRA');  // 1, 'Running', 'CURRENT_TIMESTAMP'
//                    $destColCollationName = ArrayHelper::getValue($targetExtraInfo, 'COLLATION_NAME');  // 1, 'Running', 'CURRENT_TIMESTAMP'
//
//                    if ($destColName === $colName) {
//
//                        $colAttributeError = [];
//
//                        if ($colDataType !== $destColDataType) {
//                            $colAttributeError[] = "&ensp;-Type doesn't match, original( <b>${colDataType}</b> ) modified(<b>( ${destColDataType}</b> )";
//                        }
//
//                        if (!empty($colCharMaxLength) && ($colCharMaxLength !== $destColCharMaxLength)) {
//                            $colAttributeError[] = "&ensp;-Length doesn't match, original( <b>${colCharMaxLength}</b> ) modified( <b>${destColCharMaxLength}</b> )";
//                        }
//
//                        if (!empty($colNumberPrecision) && ($colNumberPrecision !== $destColNumberPrecision)) {
//                            $colAttributeError[] = "&ensp;-Length doesn't match, original (<b>${colNumberPrecision}</b>) modified (<b>${destColNumberPrecision}</b>)";
//                        }
//
//                        if (!empty($colDatetimePrecision) && ($colDatetimePrecision !== $destColDatetimePrecision)) {
//                        }
//
//                        if (!empty($destColDefault) && ($colDefault !== $destColDefault)) {
//                            $colAttributeError[] = "&ensp;-Default value doesn't match, original (<b>${colDefault}</b>) modified (<b>${destColDefault}</b>)";
//                        }
//
//                        if (!empty($colCollationName) && ($colCollationName !== $destColCollationName)) {
//                            $colAttributeError[] = "&ensp;-Collation doesn't match, original (<b>${colCollationName}</b>) modified (<b>${destColCollationName}</b>)";
//                        }
//
//                        if (!empty($colComment) && ($colComment !== $destColComment)) {
//                            $colAttributeError[] = "&ensp;-Comment doesn't match, original (<b>${colComment}</b>) modified (<b>${destColComment}</b>)";
//                        }
//
//                        if (count($colAttributeError) > 0) {
//                            $syncObject->setError(true);
//                            $syncObject->setCol(true);
//                            $syncObject->setErrorSummary("<b>Column</b> <u>${colName}</u> attributes:");
//                            $syncObject->setErrorSummary($colAttributeError);
//
//                        }
//                    }
//                }
//            }
//        }
        //["<b>Index Key</b> ( status ) doesn't set. ","<b>Columns</b> doesn't match, original ( 4 ) Diff: ( 1 )","<b> - Absent </b> ( status )","<b>Column</b> <u>name</u> attributes erros:",["&ensp;Comment doesn't match, original (<b>Add Comments</b>) modified (<b></b>)"]]

    }

    public static function queue(int $limit)
    {
        try {
            echo "\n=== Queue Call......\n";
            $syncModels = SyncTable::find()->where(['status' => SyncTable::STATUS_TABLE_META_QUEUE])->limit($limit ?: 5)->all();
            if($syncModels){
                foreach ($syncModels as $syncModel) {
                    /** @var SyncTable $syncModel */

                    echo "\nTable ".$syncModel->tableName." \n";

                    $sourceConnection = DynamicConnection::getConnection($syncModel->source->configuration, $syncModel->source->dbname);
                    $targetConnection = DynamicConnection::getConnection($syncModel->target->configuration, $syncModel->target->dbname);
                    $sourceSchema = $sourceConnection->schema->getTableSchema($syncModel->tableName);
                    if ($sourceSchema) {
                        $targetSchema = $targetConnection->schema->getTableSchema($syncModel->tableName);
                        $sourceSchemaInfo = self::getTableInfo($sourceConnection, $syncModel->source->dbname, $syncModel->tableName);
                        $targetSchemaInfo = self::getTableInfo($targetConnection, $syncModel->target->dbname, $syncModel->tableName);
                        if ($targetSchema) {
                            self::singularSync($syncModel, $sourceConnection, $targetConnection, $sourceSchema, $targetSchema, $sourceSchemaInfo, $targetSchemaInfo);
                        } else {
                            $syncModel->isSuccess = 0;
                            $syncModel->extra = Json::encode(['schema' => $sourceSchemaInfo, 'extra' => $sourceSchemaInfo]);
                            $syncModel->status = SyncTable::STATUS_SCHEMA_COMPLETED;
                            $syncModel->errorSummary = Json::encode(["<b>" . $syncModel->tableName . "</b> table doesn't exist."]);
                            if (!$syncModel->save()) {
                                if($sourceSchema->fullName=='flight_airlines'){
                                    dd('source',$sourceSchema);
                                    dd('target', $targetSchema);
                                    echo Json::encode($syncModel->getErrors());
                                    die("error");
                                }


                                echo Json::encode($syncModel->getErrors());
                            }else{
                                if($sourceSchema->fullName=='flight_airlines'){
                                    dd('source',$sourceSchema);
                                    dd('target', $targetSchema);
                                    echo Json::encode($syncModel->getErrors());
                                    die("save");
                                }
                            }
                        }
                    }
                }

                echo "Normal Queue Create\n";
                Yii::$app->queue->delay(5)->push(new TableMetaQueueJob());
                $sourceConnection->close();
                $targetConnection->close();
                Yii::$app->db->close();

                echo "\nNew Queue added.\n";
            }
        }catch (Exception $e) {
            echo "exception: ".$e->getMessage();
            Yii::error($e->getMessage(), 'queue');
            echo "Exception Queue Create\n";
            Yii::$app->queue->delay(5)->push(new TableMetaQueueJob());
        }
    }
}