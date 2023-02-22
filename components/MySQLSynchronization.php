<?php

namespace app\components;

use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\TableCompare;
use stdClass;
use Yii;
use yii\helpers\Json;

class MySQLSynchronization
{
    public static function syncHostAndDB($id)
    {

        try {
            $model = SyncConfig::findOne(['id' => $id]);
            $db = DynamicConnection::getConnectionByModel($model);
            $database = $db->createCommand("SHOW DATABASES;")->queryAll();
            $syncHostDB = new SyncHostDb();
            foreach ($database as $name){
                $syncHostDB->host = $model->host;
                $syncHostDB->dbname = $name['Database'];
                $syncHostDB->type = $model->type;
                if($syncHostDB->save()){
                    unset($syncHostDB->id);
                    $syncHostDB->isNewRecord = true;
                }
            }
        } catch (\Exception  $e) {
            dd($e);
        }
    }

    public static function getTotalCount($db)
    {
        $tables = $db->createCommand('SHOW TABLES;')->queryAll();
        return count($tables);
    }

    public static function getStatics($db)
    {
        $prodData = [];
        $database = $db->createCommand("SELECT DATABASE()")->queryScalar();
        $tableInfo = $db->createCommand("SELECT * FROM  information_schema.TABLES WHERE  TABLE_SCHEMA = '${database}';")->queryAll();

        foreach ($tableInfo as $tableIndex => $table) {
            $tableName = $table['TABLE_NAME'];
            $signalInfo = $db->createCommand("SELECT * FROM  information_schema.COLUMNS WHERE  TABLE_SCHEMA = '${database}' AND TABLE_NAME = '${tableName}';")->queryAll();
            $prodData[$tableName]['engine'] = (string)$table['ENGINE'];
            $prodData[$tableName]['table'] = (string)$tableName;
            $prodData[$tableName]['autoIncrement'] = "";
            $prodData[$tableName]['maxId'] = "";
            $prodData[$tableName]['cols'] = (int)count($signalInfo);
            $prodData[$tableName]['rows'] = (int)$db->createCommand("SELECT COUNT(*) FROM `${tableName}`;")->queryScalar();
            $prodData[$tableName]['primary'] = [];
            $prodData[$tableName]['index'] = [];
            $prodData[$tableName]['unique'] = [];
            $prodData[$tableName]['colInfo'] = $signalInfo;
            foreach ($signalInfo as $info) {
                $colName = $info['COLUMN_NAME']; //id, uid, username
                $colKey = $info['COLUMN_KEY']; // PRI, UNI, MUL
                $colExtra = $info['EXTRA']; // auto_increment , null
                if ($colKey === 'PRI') {
                    $prodData[$tableName]['primary'][] = $colName;
                }
                if ($colKey === 'UNI') {
                    $prodData[$tableName]['unique'][] = $colName;
                }
                if ($colExtra === 'auto_increment') {
                    $prodData[$tableName]['autoIncrement'] = $colName;
                }
            }
        }

        $tableStatistics = $db->createCommand("SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '${database}'; ")->queryAll();
        foreach ($tableStatistics as $statistic) {
            $indexName = $statistic['INDEX_NAME'];  // id, PRIMARY
            $tableName = $statistic['TABLE_NAME'];
            $colName = $statistic['COLUMN_NAME'];
            if ($indexName !== 'PRIMARY') {
                $prodData[$tableName]['index'][] = $colName;
            }
        }
        return $prodData;
    }

    protected static function getSingular($table, $prod, $migrationData)
    {
        $object = new stdClass();
        $object->id = $table;
        $object->table = $table;
        $object->engine = false;
        $object->engineType = '';
        $object->primary = false;
        $object->primaryKeys = '';
        $object->autoIncrement = false;
        $object->autoIncrementKey = '';
        $object->unique = false;
        $object->uniqueKeys = '';
        $object->index = false;
        $object->indexKeys = '';
        $object->rows = 0;
        $object->cols = 0;
        $object->maxType = '';
        $object->maxId = '';
        $object->colInfo = '';
        $object->error = false;
        $object->errorSummary = [];

        if ($prod['engine'] !== $migrationData['engine']) {
            $object->engine = false;
            $object->engineType = $prod['engine'];
            $object->errorSummary[] = "<b>Engine</b> doesn't match";
        }

        if ($prod['autoIncrement'] !== $migrationData['autoIncrement']) {
            $object->autoIncrement = false;
            $object->autoIncrementKey = $prod['autoIncrement'];
            $object->error = true;
            $object->errorSummary[] = "<b>Auto Increment</b> <samp>(" . $prod['autoIncrement'] . ")<samp> doesn't set. ";
        }

        if (!empty(array_diff($prod['primary'], $migrationData['primary']))) {
            $object->primary = false;
            $object->error = true;
            $arrayDiff = array_diff($prod['primary'], $migrationData['primary']);
            $object->primaryKeys = $arrayDiff;
            if (count($arrayDiff) === 1) {
                $arrayDiff = '["' . $arrayDiff[0] . '"]';
            } else {
                $arrayDiff = json_encode($arrayDiff);
            }
            $object->errorSummary[] = "<b>Primary Key</b> doesn't match columns <samp>" . $arrayDiff . "</samp>";
        }

        if (!empty(array_diff($prod['unique'], $migrationData['unique']))) {
            $object->unique = false;
            $object->error = true;
            $arrayDiff = array_diff($prod['unique'], $migrationData['unique']);
            $object->uniqueKeys = $arrayDiff;
            if (count($arrayDiff) === 1) {
                $arrayDiff = '["' . $arrayDiff[0] . '"]';
            } else {
                $arrayDiff = json_encode($arrayDiff);
            }
            $object->errorSummary[] = "<b>Unique Key</b> doesn't match columns <samp>" . $arrayDiff . "</samp>";
        }

        if (!empty(array_diff($prod['index'], $migrationData['index']))) {
            $object->index = false;
            $object->error = true;
            $arrayDiff = array_diff($prod['index'], $migrationData['index']);
            $object->indexKeys = $arrayDiff;
            if (count($arrayDiff) > 1) {
                $arrayDiff = json_encode($arrayDiff);
            } else {
                $arrayDiff = '[ "' . $arrayDiff[0] . '" ]';
            }
            $object->errorSummary[] = "<b>Index Key</b> doesn't match <samp>" . $arrayDiff . "</samp>";
        }

        if ($prod['rows'] !== $migrationData['rows']) {
            $object->rows = false;
            $object->error = true;
            $object->errorSummary[] = '<b>Rows</b> ' . $prod['rows'] . ' Founded ' . $migrationData['rows'] . '<b><i> Diff</i></b>:  ' . ($prod['rows'] - $migrationData['rows']) . ' rows';
        }

        if ($prod['cols'] !== $migrationData['cols']) {
            $object->cols = false;
            $object->error = true;
            $object->errorSummary[] = "<b>Columns </b> doesn't match founded(<b>" . $migrationData['cols'] . ")</b> out of <b>" . $prod['cols'] . "</b>";

            $missingCol = [];

            foreach ($prod['colInfo'] as $colInfo) {
                $colName = trim($colInfo['COLUMN_NAME']); // country_code
                $colKey = trim($colInfo['COLUMN_KEY']); // PRI, UNI, MUL, ''
                if (empty($colKey)) {
                    $isColMatch = false;
                    foreach ($migrationData['colInfo'] as $migColInfo) {
                        $migColName = trim($migColInfo['COLUMN_NAME']);
                        if ($migColName === $colName) {
                            $isColMatch = true;

                        }
                    }

                    if (!$isColMatch) {
                        $missingCol[] = $colName;
                    }
                }
            }

            if (count($missingCol) > 0) {
                if (count($missingCol) > 1) {
                    $missingCol = json_encode($missingCol);
                } else {
                    $missingCol = '[ "' . $missingCol[0] . '" ]';
                }

                $object->errorSummary[] = "<samp>-<b> " . $missingCol . "</b> colmumns doesn't Found</samp>";
            }
        }

        foreach ($prod['colInfo'] as $colInfo) {
            $colName = trim($colInfo['COLUMN_NAME']); // country_code
            $colKey = trim($colInfo['COLUMN_KEY']); // PRI, UNI, MUL, ''
            $colDataType = trim($colInfo['DATA_TYPE']); // varchar, char, int, 'timestamp', '
            $colCharMaxLength = trim($colInfo['CHARACTER_MAXIMUM_LENGTH']); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION
            $colNumberPrecision = trim($colInfo['NUMERIC_PRECISION']); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
            $colDatetimePrecision = trim($colInfo['DATETIME_PRECISION']); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
            $colDefault = trim($colInfo['COLUMN_DEFAULT']); // 1, 'Running', 'CURRENT_TIMESTAMP'
            $colType = trim($colInfo['COLUMN_TYPE']); // timestamp,
            $colExtra = trim($colInfo['EXTRA']); // DEFAULT_GENERATED,
            $colCollationName = trim($colInfo['COLLATION_NAME']); // utf8mb4_unicode_ci,

            foreach ($migrationData['colInfo'] as $migColInfo) {
                $migColName = trim($migColInfo['COLUMN_NAME']);
                $migColKey = trim($migColInfo['COLUMN_KEY']); // PRI, UNI, MUL, ''
                $migColDataType = trim($migColInfo['DATA_TYPE']); // varchar, char, int, 'timestamp', '
                $migColCharMaxLenght = trim($migColInfo['CHARACTER_MAXIMUM_LENGTH']); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION
                $migColNumberPrecision = trim($migColInfo['NUMERIC_PRECISION']); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
                $migColDateTimePrecision = trim($migColInfo['DATETIME_PRECISION']); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
                $migColDefault = trim($migColInfo['COLUMN_DEFAULT']); // 1, 'Running', 'CURRENT_TIMESTAMP'
                $migColType = trim($migColInfo['COLUMN_TYPE']); // timestamp,
                $migColExtra = trim($migColInfo['EXTRA']); // DEFAULT_GENERATED,
                $migColCollationName = trim($migColInfo['COLLATION_NAME']); // utf8mb4_unicode_ci
                if ($migColName === $colName) {

                    $colAttributeError = [];

                    if ($table === 'userProfile' && $colName === 'profilePicture') {
                        //dd($colInfo, $migColInfo, $colCollationName, $migColCollationName);die();
                    }

                    if ($colDataType !== $migColDataType) {
                        $colAttributeError[] = "&ensp;<samp>type doesn't match actual(<b>${colDataType}</b>) founded(<b>(${migColDataType}</b>)</samp>";
                    }

                    if (!empty($colCharMaxLength) && ($colCharMaxLength !== $migColCharMaxLenght)) {
                        $colAttributeError[] = "&ensp;<samp>length doesn't match actual(<b>${colCharMaxLength}</b>) founded(<b>${migColCharMaxLenght}</b>)</samp>";
                    }

                    if (!empty($colNumberPrecision) && ($colNumberPrecision !== $migColNumberPrecision)) {
                        $colAttributeError[] = "&ensp;<samp>length doesn't match actual(<b>${colNumberPrecision}</b>) founded(<b>${migColNumberPrecision}</b>)</samp>";
                    }

                    if (!empty($colDatetimePrecision) && ($colDatetimePrecision !== $migColDateTimePrecision)) {
                    }

                    if (!empty($migColDefault) && ($colDefault !== $migColDefault)) {
                        $colAttributeError[] = "&ensp;<samp>default value doesn't match actual(<b>${colDefault}</b>) founded(<b>${migColDefault}</b>)</samp>";
                    }

                    if (!empty($colCollationName) && ($colCollationName !== $migColCollationName)) {
                        $colAttributeError[] = "&ensp;<samp>collation doesn't match actual(<b>${colCollationName}</b>) founded(<b>${migColCollationName}</b>)</samp>";
                    }

                    if (count($colAttributeError) > 0) {
                        $object->error = true;
                        $object->errorSummary[] = "<b>Column</b> <u>${colName}</u> attributes erros:";
                        $object->errorSummary = array_merge($object->errorSummary, $colAttributeError);
                    }
                }
            }

        }


        if ($prod['maxId'] !== $migrationData['maxId'] && ($prod['primary'] === $migrationData['primary'])) {
            $object->maxId = false;
            $object->error = true;
            $object->errorSummary[] = "<b>MaxId</b> doesn't match columnsed: " . $prod['maxId'] . ' Founded ' . $migrationData['maxId'];
        }

        return $object;

    }

    public
    static function combinedData($prodData, $migrationsData)
    {
        $map = [];
        foreach ($prodData as $table => $prod) {
            if (isset($migrationsData[$table])) {
                $migrationData = $migrationsData[$table];
                $map[] = self::getSingular($table, $prod, $migrationData);
            } else {
                $object = new stdClass();
                $object->id = $table;
                $object->table = $table;
                $object->engine = false;
                $object->engineType = '';
                $object->primary = false;
                $object->primaryKeys = '';
                $object->autoIncrement = false;
                $object->autoIncrementKey = '';
                $object->unique = false;
                $object->uniqueKeys = '';
                $object->index = false;
                $object->indexKeys = '';
                $object->rows = 0;
                $object->cols = 0;
                $object->maxType = '';
                $object->maxId = '';
                $object->colInfo = '';
                $object->error = true;
                $object->errorSummary = ["<b>${table}</b> table doesn't exist."];
                $map[] = $object;
            }
        }

        return (object)$map;
    }

    public
    static function bulkInsert()
    {
        $sourceDB = Yii::$app->sourceDB;
        $destinationDB = Yii::$app->destinationDB;

        $prodData = MySQLSynchronization::getStatics($sourceDB);
        $migrateData = MySQLSynchronization::getStatics($destinationDB);
        $mappingData = MySQLSynchronization::combinedData($prodData, $migrateData);
        //dd($mappingData); die();
        if ($mappingData) {
            $tableCompare = new TableCompare();
            foreach ($mappingData as $object) {
                $tableCompare->tableName = $object->table;
                $tableCompare->isEngine = (int)$object->engine;
                $tableCompare->engineType = $object->engineType;
                $tableCompare->autoIncrement = (int)$object->autoIncrement;
                $tableCompare->autoIncrementKey = $object->autoIncrementKey;
                $tableCompare->isPrimary = (int)$object->primary;
                $tableCompare->primaryKeys = Json::encode($object->primaryKeys);
                $tableCompare->isUnique = (int)$object->unique;
                $tableCompare->uniqueKeys = Json::encode($object->uniqueKeys);
                $tableCompare->isIndex = (int)$object->index;
                $tableCompare->indexKeys = Json::encode($object->indexKeys);
                $tableCompare->maxColType = (string)$object->maxId;
                $tableCompare->maxColValue = (string)$object->maxId;
                $tableCompare->cols = (int)$object->cols;
                $tableCompare->rows = (int)$object->rows;
                $tableCompare->columnStatics = Json::encode($object->colInfo);
                $tableCompare->isError = (int)$object->error;
                $tableCompare->errorSummary = Json::encode($object->errorSummary);
                $tableCompare->status = TableCompare::STATUS_PULL;
                $tableCompare->createdAt = date('Y-m-d h:i:s');
                $tableCompare->processedAt = date('Y-m-d h:i:s');
                if ($tableCompare->save()) {
                    unset($tableCompare->id);
                    $tableCompare->isNewRecord = true;
                } else {
                    dd($tableCompare->getErrors());
                    die();
                }
            }
        }
    }

}