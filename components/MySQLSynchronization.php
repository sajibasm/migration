<?php

namespace app\components;

use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use stdClass;
use Yii;
use yii\helpers\Json;

class MySQLSynchronization
{
    public static function syncHostAndDB($id)
    {

        try {
            $newRecords = 0;
            $model = SyncConfig::findOne(['id' => $id]);
            $db = DynamicConnection::getConnectionByModel($model);
            $database = $db->createCommand("SHOW DATABASES;")->queryAll();
            $syncHostDB = new SyncHostDb();
            foreach ($database as $name) {
                $syncHostDB->host = $model->host;
                $syncHostDB->dbname = $name['Database'];
                $syncHostDB->type = $model->type;
                if($syncHostDB->save()){
                    unset($syncHostDB->id);
                    $syncHostDB->isNewRecord = true;
                    $newRecords++;
                }
            }

            return $newRecords?:false;
        } catch (\Exception  $e) {
            dd($e);
        }
    }

    public static function getTotalCount($db)
    {
        $tables = $db->createCommand('SHOW TABLES;')->queryAll();
        return count($tables);
    }

    public static function getStatics($connection, $database)
    {
        $data = [];
        $tableInfo = $connection->createCommand("SELECT * FROM  information_schema.TABLES WHERE  TABLE_SCHEMA = '${database}';")->queryAll();
        foreach ($tableInfo as $tableIndex => $table) {


            $tableName = $table['TABLE_NAME'];
            $signalInfo = $connection->createCommand("SELECT * FROM  information_schema.COLUMNS WHERE  TABLE_SCHEMA = '${database}' AND TABLE_NAME = '${tableName}';")->queryAll();
            $data[$tableName]['host'] = (string)$connection->dsn;
            $data[$tableName]['database'] = (string)$database;
            $data[$tableName]['engine'] = (string)$table['ENGINE'];
            $data[$tableName]['table'] = (string)$tableName;
            $data[$tableName]['autoIncrement'] = "";
            $data[$tableName]['maxId'] = "";
            $data[$tableName]['cols'] = (int)count($signalInfo);
            $data[$tableName]['rows'] = (int)$connection->createCommand("SELECT COUNT(*) FROM `${tableName}`;")->queryScalar();
            $data[$tableName]['primary'] = [];
            $data[$tableName]['index'] = [];
            $data[$tableName]['unique'] = [];
            $data[$tableName]['colInfo'] = $signalInfo;

            foreach ($signalInfo as $info) {
                $colName = $info['COLUMN_NAME']; //id, uid, username
                $colKey = $info['COLUMN_KEY']; // PRI, UNI, MUL
                $colExtra = $info['EXTRA']; // auto_increment , null
                if ($colKey === 'PRI') {
                    $data[$tableName]['primary'][] = $colName;
                }
                if ($colKey === 'UNI') {
                    $data[$tableName]['unique'][] = $colName;
                }
                if ($colExtra === 'auto_increment') {
                    $data[$tableName]['autoIncrement'] = $colName;
                }
            }
        }

        $tableStatistics = $connection->createCommand("SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '${database}'; ")->queryAll();
        $tableStatistics = $connection->createCommand("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = 'yii2' AND REFERENCED_TABLE_NAME = 'user'; ")->queryAll();

        if($table['TABLE_NAME']=='user'){
            dd($tableStatistics);
            die();
        }

        foreach ($tableStatistics as $statistic) {
            $indexName = $statistic['INDEX_NAME'];  // id, PRIMARY
            $tableName = $statistic['TABLE_NAME'];
            $colName = $statistic['COLUMN_NAME'];
            if ($indexName !== 'PRIMARY') {
                $data[$tableName]['index'][] = $colName;
            }
        }
        return $data;
    }

    protected static function getSingular($table, $source, $destination, $sourceHost, $destinationHost)
    {
        $syncObject = new SyncObject();
        $syncObject->setTable($table);
        $syncObject->setSourceHost($sourceHost);
        $syncObject->setDestinationHost($destinationHost);

        if ($source['engine'] !== $destination['engine']) {
            $syncObject->setEngine(false);
            $syncObject->setEngineType($source['engine']);
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>Engine</b> doesn't match");
        }

        if ($source['autoIncrement'] !== $destination['autoIncrement']) {
            $syncObject->setAutoIncrement(false);
            $syncObject->setAutoIncrementKeys($source['autoIncrement']);
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>Auto Increment</b> <samp>(" . $source['autoIncrement'] . ")<samp> doesn't set. ");
        }

        if (!empty(array_diff($source['primary'], $destination['primary']))) {
            $syncObject->setPrimary(false);
            $syncObject->setError(true);
            $arrayDiff = array_diff($source['primary'], $destination['primary']);
            $syncObject->setPrimaryKeys($arrayDiff);
            if (count($arrayDiff) === 1) {
                $arrayDiff = '["' . $arrayDiff[0] . '"]';
            } else {
                $arrayDiff = json_encode($arrayDiff);
            }
            $syncObject->setErrorSummary("<b>Primary Key</b> doesn't match columns <samp>" . $arrayDiff . "</samp>");
        }

        if (!empty(array_diff($source['unique'], $destination['unique']))) {
            $syncObject->setUnique(false);
            $syncObject->setError(true);
            $arrayDiff = array_diff($source['unique'], $destination['unique']);
            $syncObject->setUniqueKeys($arrayDiff);
            if (count($arrayDiff) === 1) {
                $arrayDiff = '["' . $arrayDiff[0] . '"]';
            } else {
                $arrayDiff = json_encode($arrayDiff);
            }
            $syncObject->setErrorSummary("<b>Unique Key</b> doesn't match columns <samp>" . $arrayDiff . "</samp>");
        }

        if (!empty(array_diff($source['index'], $destination['index']))) {
            $syncObject->setIndex(false);
            $syncObject->setError(true);
            $arrayDiff = array_diff($source['index'], $destination['index']);
            $syncObject->setIndexKeys($arrayDiff);
            if (count($arrayDiff) > 1) {
                $arrayDiff = json_encode($arrayDiff);
            } else {
                $arrayDiff = '[ "' . $arrayDiff[0] . '" ]';
            }
            $syncObject->setErrorSummary("<b>Index Key</b> doesn't match <samp>" . $arrayDiff . "</samp>");
        }

        if ($source['rows'] !== $destination['rows']) {
            $syncObject->setRows(false);
            $syncObject->setNumberOfRows($source['rows']);
            $syncObject->setError(true);
            $syncObject->setErrorSummary('<b>Rows</b> ' . $source['rows'] . ' Founded ' . $destination['rows'] . '<b><i> Diff</i></b>:  ' . ($source['rows'] - $destination['rows']) . ' rows');
        }

        if ($source['cols'] !== $destination['cols']) {
            $syncObject->setCol(false);
            $syncObject->setNumberOfCols($source['cols']);
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>Columns </b> doesn't match founded(<b>" . $destination['cols'] . ")</b> out of <b>" . $source['cols'] . "</b>");

            $missingCol = [];

            foreach ($source['colInfo'] as $colInfo) {
                $colName = trim($colInfo['COLUMN_NAME']); // country_code
                $colKey = trim($colInfo['COLUMN_KEY']); // PRI, UNI, MUL, ''
                if (empty($colKey)) {
                    $isColMatch = false;
                    foreach ($destination['colInfo'] as $migColInfo) {
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

                $syncObject->setErrorSummary("<b>Columns </b> doesn't match founded(<b>" . $destination['cols'] . ")</b> out of <b>" . $source['cols'] . "</b>");
            }
        }

        foreach ($source['colInfo'] as $colInfo) {
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

            foreach ($destination['colInfo'] as $migColInfo) {
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
                        $syncObject->setError(true);
                        $syncObject->setErrorSummary("<b>Column</b> <u>${colName}</u> attributes erros:");
                        $syncObject->setErrorSummary(array_merge($syncObject->getErrorSummary(), $colAttributeError));
                    }
                }
            }

        }


        if ($source['maxId'] !== $destination['maxId'] && ($source['primary'] === $destination['primary'])) {
            $syncObject->setMax(false);
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>MaxId</b> doesn't match columnsed: " . $source['maxId'] . ' Founded ' . $destination['maxId']);
        }

        return $syncObject;
    }

    public
    static function combinedData($sourceData, $destinationData, $sourceHost, $destinationHost)
    {

        $map = [];
        foreach ($sourceData as $table => $source) {
            if (isset($destinationData[$table])) {
                $traget = $destinationData[$table];
                $map[] = self::getSingular($table, $source, $traget, $sourceHost, $destinationHost);
            } else {
                $object = new stdClass();
                $object->id = $table;
                $object->sourceHost = $sourceHost;
                $object->destinationHost = $destinationHost;
                $object->table = $table;
                $object->engine = true;
                $object->engineType = '';
                $object->primary = true;
                $object->primaryKeys = '';
                $object->autoIncrement = true;
                $object->autoIncrementKey = '';
                $object->unique = true;
                $object->uniqueKeys = '';
                $object->index = true;
                $object->indexKeys = '';
                $object->isCols = true;
                $object->numberOfCols = 0;
                $object->isRows = true;
                $object->numberOfRows = 0;
                $object->maxType = '';
                $object->maxId = '';
                $object->colInfo = '';
                $object->error = false;
                $object->errorSummary = ["<b>${table}</b> table doesn't exist."];
                $map[] = $object;
            }
        }

        return (object)$map;
    }

    public static function process(SyncHostDb $source, SyncHostDb $destination)
    {
        $sourceConfigModel = SyncConfig::findOne(['type' => $source->type]);
        $destinationConfigModel = SyncConfig::findOne(['type' => $destination->type]);

        $sourceConnection = DynamicConnection::createConnection($sourceConfigModel, $source->dbname);
        $destinationConnection = DynamicConnection::createConnection($destinationConfigModel, $destination->dbname);
        $sourceData = MySQLSynchronization::getStatics($sourceConnection, $source->dbname);
        $DestinationData = MySQLSynchronization::getStatics($destinationConnection, $destination->dbname);
        $mappingData = MySQLSynchronization::combinedData($sourceData, $DestinationData, $sourceConnection->dsn, $destinationConnection->dsn);

        if ($mappingData) {
            $rows = [];
            foreach ($mappingData as $object) {
                $rows[] = [
                    null,
                    $source->id,
                    $destination->id,
                    $object->table,
                    (int)$object->engine,
                    $object->engineType,
                    (int)$object->autoIncrement,
                    $object->autoIncrementKey,
                    (int)$object->primary,
                    Json::encode($object->primaryKeys),
                    (int)$object->unique,
                    Json::encode($object->uniqueKeys),
                    (int)$object->index,
                    Json::encode($object->indexKeys),
                    (string)$object->maxId,
                    (string)$object->maxId,
                    (int)$object->isCols,
                    (int)$object->numberOfCols,
                    (int)$object->isRows,
                    (int)$object->numberOfRows,
                    Json::encode($object->colInfo),
                    (int)$object->error,
                    Json::encode($object->errorSummary),
                    SyncTable::STATUS_PULL,
                    date('Y-m-d h:i:s'),
                    date('Y-m-d h:i:s')
                ];
            }

            Yii::$app->db->createCommand()->batchInsert(SyncTable::tableName(), [
                'id', 'sourceDb', 'destinationDb', 'tableName', 'isEngine', 'engineType',
                'autoIncrement', 'autoIncrementKey', 'isPrimary', 'primaryKeys',
                'isUnique', 'uniqueKeys', 'isIndex', 'indexKeys', 'maxColType', 'maxColValue',
                'isCols', 'numberOfCols', 'isRows', 'numberOfRows', 'columnStatics', 'isError', 'errorSummary', 'status', 'createdAt', 'processedAt'
            ], $rows)->execute();

        }

    }

}