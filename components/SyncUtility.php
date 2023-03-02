<?php

namespace app\components;

use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use BankDb\BankDb;
use BankDb\BankDbException;
use stdClass;
use Yii;
use yii\db\Connection;
use yii\db\mssql\Schema;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
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

    private static function singularSync(SyncTable &$syncModel, $sourceSchema, $targetSchema, $sourceSchemaInfo, $targetSchemaInfo)
    {

        $errorSummary = [];
        $syncModel->extra = Json::encode(['schema' => $sourceSchema, 'info' => $sourceSchemaInfo]);

        //dd($sourceSchemaInfo);
        //die();


        //Check Engine type
        if (ArrayHelper::getValue($sourceSchemaInfo, 'ENGINE')) {
            if (ArrayHelper::getValue($targetSchemaInfo, 'ENGINE')) {
                if ((ArrayHelper::getValue($sourceSchemaInfo, 'ENGINE') !== ArrayHelper::getValue($targetSchemaInfo, 'ENGINE'))) {
                    $errorSummary[] = "<b>Engine</b> (" . $syncModel->tableName . ") doesn't match ";
                    $syncModel->isEngine = false;
                    $syncModel->isSuccess = false;
                }
            }
        }

        if (ArrayHelper::getValue($sourceSchema, 'primaryKey')) {
            $emptyPrimaryKeys = [];
            if (ArrayHelper::getValue($targetSchema, 'primaryKey')) {
                foreach (ArrayHelper::getValue($sourceSchema, 'primaryKey') as $sourcePrimary) {
                    foreach (ArrayHelper::getValue($targetSchema, 'primaryKey') as $targetPrimary) {
                        if ($sourcePrimary !== $targetPrimary) {
                            $emptyPrimaryKeys[] = $sourcePrimary;
                        }
                    }
                }
            } else {
                $emptyPrimaryKeys = ArrayHelper::getValue($sourceSchema, 'primaryKey');
            }

            $errorSummary[] = "<b>Primary Key</b> doesn't set( " . implode(", ", $emptyPrimaryKeys) . " )";
            $syncModel->isPrimary = false;
            $syncModel->isSuccess = false;
        }

        dd($errorSummary);
        dd($sourceSchema);
        dd($targetSchema);
        die();

//        //find Auto Increment
//        if (ArrayHelper::getValue($sourceTable, 'autoIncrement') && !ArrayHelper::getValue($targetTable, 'autoIncrement')) {
//            $syncObject->setAutoIncrement(true);
//            $syncObject->setAutoIncrementKeys(ArrayHelper::getValue($sourceTable, 'extra.column.autoIncrement.COLUMN_NAME'));
//            $syncObject->setErrorSummary("<b>Auto Increment</b> (" . ArrayHelper::getValue($sourceTable, 'extra.column.autoIncrement.COLUMN_NAME') . ") doesn't set. ");
//        }
//
//        //check unique columns
//        if (ArrayHelper::getValue($sourceTable, 'unique')) {
//            $sourceUniqueInfo = ArrayHelper::getValue($sourceTable, 'extra.column.unique');
//            $destinationUniqueInfo = ArrayHelper::getValue($targetTable, 'extra.column.unique');
//            if (ArrayHelper::getValue($targetTable, 'unique')) {
//                $uniqueMissingCols = [];
//                foreach ($sourceUniqueInfo as $srcColumn) {
//                    $isFound = false;
//                    foreach ($destinationUniqueInfo as $desColumn) {
//                        if ($desColumn['COLUMN_NAME'] === $srcColumn['COLUMN_NAME']) {
//                            $isFound = true;
//                        }
//                    }
//                    if (!$isFound) {
//                        $uniqueMissingCols[] = $srcColumn['COLUMN_NAME'];
//                    }
//                }
//                if ($uniqueMissingCols) {
//                    $syncObject->setUnique(true);
//                    $syncObject->setUniqueKeys($uniqueMissingCols);
//                    $syncObject->setError(true);
//                    $syncObject->setErrorSummary("<b>Unique Key</b> ( " . implode(", ", $uniqueMissingCols) . " ) doesn't set. ");
//                }
//            } else {
//                $uniqueColumns = [];
//                foreach ($sourceUniqueInfo as $column) {
//                    $uniqueColumns[] = $column['COLUMN_NAME'];
//                }
//                $syncObject->setUnique(true);
//                $syncObject->setUniqueKeys($uniqueColumns);
//                $syncObject->setError(true);
//                $syncObject->setErrorSummary("<b>Unique Key</b> ( " . implode(", ", $uniqueColumns) . " ) doesn't set. ");
//            }
//        }
//
//        if (ArrayHelper::getValue($sourceTable, 'foreign')) {
//            $sourceForeignInfo = ArrayHelper::getValue($sourceTable, 'extra.column.foreign');
//            $destinationForeignInfo = ArrayHelper::getValue($targetTable, 'extra.column.foreign');
//            if (ArrayHelper::getValue($targetTable, 'foreign')) {
//                $foreignMissingCols = [];
//                foreach ($sourceForeignInfo as $srcColumn) {
//                    $isFound = false;
//                    foreach ($destinationForeignInfo as $desColumn) {
//                        if (ArrayHelper::getValue($srcColumn, 'foreign_key') === ArrayHelper::getValue($desColumn, 'foreign_key')) {
//                            $isFound = true;
//                        }
//                    }
//                    if (!$isFound) {
//                        $foreignMissingCols[] = ArrayHelper::getValue($srcColumn, 'foreign_key');
//                    }
//                }
//                if ($foreignMissingCols) {
//                    $syncObject->setForeign(true);
//                    $syncObject->setUniqueKeys($foreignMissingCols);
//                    $syncObject->setError(true);
//                    $syncObject->setErrorSummary("<b>Foreign Key</b> ( " . implode(", ", $foreignMissingCols) . " ) doesn't set. ");
//                }
//            } else {
//                $foreignColumns = [];
//                foreach ($sourceForeignInfo as $column) {
//                    $foreignColumns[] = ArrayHelper::getValue($column, 'foreign_key');
//                }
//                $syncObject->setForeign(true);
//                $syncObject->setForeignKeys($foreignColumns);
//                $syncObject->setError(true);
//                $syncObject->setErrorSummary("<b>Foreign Key</b> ( " . implode(", ", $foreignColumns) . " ) doesn't set. ");
//            }
//        }
//
//        if (ArrayHelper::getValue($sourceTable, 'index')) {
//            $sourceIndexCols = ArrayHelper::getValue($sourceTable, 'extra.column.index');
//            $targetIndexCols = ArrayHelper::getValue($targetTable, 'extra.column.index');
//
//            if (ArrayHelper::getValue($targetTable, 'index') && $targetIndexCols) {
//                $IndexMissingCols = [];
//                foreach ($sourceIndexCols as $sourceIndexCol) {
//                    $isFound = false;
//                    foreach ($targetIndexCols as $targetColumn) {
//                        if ($targetColumn['COLUMN_NAME'] === $sourceIndexCol['COLUMN_NAME']) {
//                            $isFound = true;
//                        }
//                    }
//                    if (!$isFound) {
//                        $IndexMissingCols[] = $sourceIndexCol['COLUMN_NAME'];
//                    }
//                }
//
//                if ($IndexMissingCols) {
//                    $syncObject->setIndex(true);
//                    $syncObject->setIndexKeys($IndexMissingCols ?: []);
//                    $syncObject->setError(true);
//                    $syncObject->setErrorSummary("<b>Index Key</b> ( " . implode(", ", $IndexMissingCols) . " ) doesn't set. ");
//                }
//            } else {
//                $indexColumns = [];
//                foreach ($sourceIndexCols as $sourceIndexColumn) {
//                    $indexColumns[] = $sourceIndexColumn['COLUMN_NAME'];
//                }
//                $syncObject->setIndex(true);
//                $syncObject->setIndexKeys($indexColumns ?: []);
//                $syncObject->setError(true);
//                $syncObject->setErrorSummary("<b>Index Key</b> ( " . implode(", ", $indexColumns) . " ) doesn't set. ");
//            }
//        }
//
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

        //dd($syncObject);
        //die($syncObject->getAutoIncrementKeys());
    }

    public static function queue()
    {
        $limit = 1;

        $syncModels = SyncTable::find()->where(['status' => SyncTable::STATUS_TABLE_META_QUEUE])->limit($limit ?: 5)->all();
        foreach ($syncModels as $syncModel) {
            /** @var SyncTable $syncModel */
            $sourceConnection = DynamicConnection::getConnection($syncModel->source->configuration, $syncModel->source->dbname);
            $targetConnection = DynamicConnection::getConnection($syncModel->target->configuration, $syncModel->target->dbname);
            $sourceSchema = $sourceConnection->schema->getTableSchema($syncModel->tableName);

            if ($sourceSchema) {
                $targetSchema = $targetConnection->schema->getTableSchema($syncModel->tableName);
                $sourceSchemaInfo = self::getTableInfo($sourceConnection, $syncModel->source->dbname, $syncModel->tableName);
                $targetSchemaInfo = self::getTableInfo($targetConnection, $syncModel->target->dbname, $syncModel->tableName);

                if ($targetSchema) {
                    self::singularSync($syncModel, $sourceSchema, $targetSchema, $sourceSchemaInfo, $targetSchemaInfo);
                } else {
                    $syncModel->isSuccess = false;
                    $syncModel->extra = ['schema' => $sourceSchemaInfo, 'extra' => $sourceSchemaInfo];
                    $syncModel->errorSummary = Json::encode(["<b>" . $syncModel->tableName . "</b> table doesn't exist."]);
                    if ($syncModel->save()) {
                        return true;
                        //TODO create another Queue.
                    }
                }
            }

        }
        die();
    }
}