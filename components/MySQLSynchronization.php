<?php

namespace app\components;

use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use stdClass;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
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
                if ($syncHostDB->save()) {
                    unset($syncHostDB->id);
                    $syncHostDB->isNewRecord = true;
                    $newRecords++;
                }
            }

            return $newRecords ?: false;
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
        $statData = [];
        $tableInfos = $connection->createCommand("SELECT * FROM  information_schema.TABLES WHERE  TABLE_SCHEMA = '${database}';")->queryAll();
        $columnConstraints = $connection->createCommand("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = '${database}';  ")->queryAll();
        $foreignKeyInfo = $connection->createCommand("SELECT CONCAT(table_name, '.', column_name) AS 'foreign_key', CONCAT(referenced_table_name, '.', referenced_column_name) AS 'references', constraint_name AS 'constraint_name' FROM information_schema.key_column_usage WHERE referenced_table_name IS NOT NULL AND table_schema = '${database}';")->queryAll();
        $indexKeyInfo = $connection->createCommand("SELECT DISTINCT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '${database}';")->queryAll();

        foreach ($tableInfos as $tableIndex => $tableInfo) {

            $tableName = $tableInfo['TABLE_NAME'];
            $colsInfo = $connection->createCommand("SELECT * FROM  information_schema.COLUMNS WHERE  TABLE_SCHEMA = '${database}' AND TABLE_NAME = '${tableName}';")->queryAll();
            $statData[$tableName]['host'] = (string)$connection->dsn;
            $statData[$tableName]['database'] = (string)$database;
            $statData[$tableName]['engine'] = strlen($tableInfo['ENGINE']) ? true : false;
            $statData[$tableName]['engineType'] = (string)$tableInfo['ENGINE'];
            $statData[$tableName]['table'] = (string)$tableName;
            $statData[$tableName]['autoIncrement'] = false;
            $statData[$tableName]['primary'] = false;
            $statData[$tableName]['foreign'] = false;
            $statData[$tableName]['unique'] = false;
            $statData[$tableName]['index'] = false;


            $indexCols = [];
            $uniqueCols = [];
            $primaryCols = [];
            $foreignCols = [];
            $constraintsCols = [];
            $autoIncrementCols = [];

            foreach ($indexKeyInfo as $index) {
                if ($index['TABLE_NAME'] === $tableName) {
                    $indexCols[] = $index;
                    $statData[$tableName]['index'] = true;
                }
            }

            foreach ($columnConstraints as $const) {
                if ($const['TABLE_NAME'] === $tableName) {
                    $constraintsCols[] = $const;
                    if ($const['CONSTRAINT_TYPE'] === 'UNIQUE') {
                        $statData[$tableName]['unique'] = true;
                    }
                    if ($const['CONSTRAINT_TYPE'] === 'PRIMARY KEY') {
                        $statData[$tableName]['primary'] = true;
                    }
                    $statData[$tableName]['index'] = true;
                }
            }

            foreach ($foreignKeyInfo as $foreign) {
                $explode = explode(".", $foreign['foreign_key']);
                if ($explode && $explode[0] === $tableName) {
                    $foreignCols[] = $foreign;
                    $statData[$tableName]['foreign'] = true;
                }
                $statData[$tableName]['index'] = true;
            }

            foreach ($colsInfo as $info) {
                $table = $info['TABLE_NAME'];
                $colKey = $info['COLUMN_KEY']; //PRI
                $extra = $info['EXTRA']; //auto_increment
                if ($tableName === $table) {
                    if ($extra === 'auto_increment') {
                        $statData[$tableName]['autoIncrement'] = true;
                        $autoIncrementCols = $info;
                    }
                    if ($colKey === 'PRI') {
                        $primaryCols[] = $info;
                    }
                    if ($colKey === 'UNI') {
                        $uniqueCols[] = $info;
                    }
                }
            }

            $statData[$tableName]['extra'] = [
                'table' => [
                    'info' => $tableInfo
                ],
                'column' => [
                    'total' => count($colsInfo),
                    'autoIncrement' => $autoIncrementCols,
                    'primary' => $primaryCols,
                    'foreign' => $foreignCols,
                    'unique' => $uniqueCols,
                    'index' => $indexCols,
                    'constraints' => $constraintsCols,
                    'info' => $colsInfo,
                ],
                'row' => [
                    'total' => (int)$connection->createCommand("SELECT COUNT(*) FROM `${tableName}`;")->queryScalar()
                ]
            ];
        }

        return $statData;
    }

    protected static function singularSyncObject($table, $source, $destination, $sourceHost, $destinationHost): SyncObject
    {
        $syncObject = new SyncObject();
        $syncObject->setTable($table);
        $syncObject->setSourceHost($sourceHost);
        $syncObject->setDestinationHost($destinationHost);

        //Check Engine type
        if (ArrayHelper::getValue($source, 'engine')) {
            if (ArrayHelper::getValue($destination, 'engine')) {
                if ((ArrayHelper::getValue($source, 'engineType') !== ArrayHelper::getValue($destination, 'engineType'))) {
                    $syncObject->setEngine(true);
                    $syncObject->setEngineType(ArrayHelper::getValue($source, 'engineType'));
                    $syncObject->setError(true);
                    $syncObject->setErrorSummary("<b>Engine</b><samp> (" . ArrayHelper::getValue($source, 'engineType') . ")</samp> doesn't match ");
                }
            } else {
                $syncObject->setEngine(true);
                $syncObject->setEngineType(ArrayHelper::getValue($source, 'engineType'));
                $syncObject->setError(true);
                $syncObject->setErrorSummary("<b>Engine</b> doesn't set.");
            }
        }

        //Find all primary keys
        if (ArrayHelper::getValue($source, 'primary')) {
            $sourcePrimaryCols = ArrayHelper::getValue($source, 'extra.column.primary');
            $destinationPrimaryCols = ArrayHelper::getValue($destination, 'extra.column.primary');
            if ($destinationPrimaryCols) {
                $isSetError = false;
                foreach ($sourcePrimaryCols as $sourcePrimaryCol) {
                    foreach ($destinationPrimaryCols as $destinationPrimaryCol) {
                        if ((ArrayHelper::getValue($sourcePrimaryCol, 'COLUMN_NAME') === ArrayHelper::getValue($destinationPrimaryCol, 'COLUMN_NAME')) &&
                            (ArrayHelper::getValue($sourcePrimaryCol, 'COLUMN_KEY') === ArrayHelper::getValue($destinationPrimaryCol, 'COLUMN_KEY'))
                        ) {
                            $diff = array_diff($sourcePrimaryCol, $destinationPrimaryCol);
                            if ($diff['DATA_TYPE']) {
                                if ($isSetError) {
                                    $syncObject->setPrimary(true);
                                    $syncObject->setErrorSummary("<b> - </b> <samp> set(" . ArrayHelper::getValue($sourcePrimaryCol, 'DATA_TYPE') . ")</samp> founded<samp> (" . ArrayHelper::getValue($destinationPrimaryCol, 'DATA_TYPE') . ")</samp>");
                                } else {
                                    $syncObject->setPrimary(true);
                                    $syncObject->setErrorSummary("<b>Primary Key</b> doesn't match, <samp> set(" . ArrayHelper::getValue($sourcePrimaryCol, 'DATA_TYPE') . ")</samp> founded<samp> (" . ArrayHelper::getValue($destinationPrimaryCol, 'DATA_TYPE') . ")</samp>");
                                }
                            }
                        } else {
                            if ($isSetError) {
                                $syncObject->setPrimary(true);
                                $syncObject->setErrorSummary("<b> - </b> <samp> set(" . ArrayHelper::getValue($sourcePrimaryCol, 'DATA_TYPE') . ")</samp> founded<samp> (" . ArrayHelper::getValue($destinationPrimaryCol, 'DATA_TYPE') . ")</samp>");
                            } else {
                                $syncObject->setPrimary(true);
                                $syncObject->setErrorSummary("<b>Primary Key</b> doesn't match, <samp> set(" . ArrayHelper::getValue($sourcePrimaryCol, 'DATA_TYPE') . ")</samp> founded<samp> (" . ArrayHelper::getValue($destinationPrimaryCol, 'DATA_TYPE') . ")</samp>");
                            }
                        }
                    }
                    $isSetError = true;
                }
            } else {
                $multipleCols = [];
                foreach ($sourcePrimaryCols as $sourcePrimaryCol) {
                    $multipleCols[] = $sourcePrimaryCol['COLUMN_NAME'] . "[" . $sourcePrimaryCol['DATA_TYPE'] . "]";
                }
                $syncObject->setPrimary(true);
                $syncObject->setErrorSummary("<b>Primary Key</b><samp> doesn't set( " . implode($multipleCols, ", ") . " )</samp>");
            }
        }

        //find Auto Increment
        if ((ArrayHelper::getValue($source, 'autoIncrement') && !ArrayHelper::getValue($destination, 'autoIncrement'))) {
            $syncObject->setAutoIncrement(true);
            $syncObject->setErrorSummary("<b>Auto Increment</b> <samp>(" . ArrayHelper::getValue($source, 'extra.column.autoIncrement.COLUMN_NAME') . ")</samp> doesn't set. ");
        }

        //check unique columns
        if (ArrayHelper::getValue($source, 'unique')) {
            if (ArrayHelper::getValue($destination, 'unique')) {
                //match all columns
                $syncObject->setUnique(true);
            } else {
                //does  not match any of the columns
                $syncObject->setUnique(true);
            }
        }

        if (ArrayHelper::getValue($source, 'foreign')) {
            if (ArrayHelper::getValue($destination, 'foreign')) {
                //match all columns
                $syncObject->setForeign(true);
            } else {
                //does  not match any of the columns
                $syncObject->setForeign(true);
            }
        }

        if (ArrayHelper::getValue($source, 'index')) {
            $sourceIndexCols = ArrayHelper::getValue($source, 'extra.column.index');
            $destinationIndexCols = ArrayHelper::getValue($destination, 'extra.column.index');
            if ($destinationIndexCols) {
                if (ArrayHelper::getValue($destination, 'index')) {
                    //match all columns
                    $missingCols = [];
                    foreach ($sourceIndexCols as $srcColumn) {
                        $isFound = false;
                        foreach ($destinationIndexCols as $desColumn) {
                            if ($desColumn['COLUMN_NAME'] === $srcColumn['COLUMN_NAME']) {
                                $isFound = true;
                            }
                        }
                        if (!$isFound) {
                            $missingCols[] = $srcColumn['COLUMN_NAME'];
                        }
                    }
                    $syncObject->setIndex(true);
                    $syncObject->setErrorSummary("<b>Index</b> <samp>( " . implode(", ", $missingCols) . " )</samp> doesn't set. ");
                } else {
                    $columns = [];
                    foreach ($sourceIndexCols as $column) {
                        $columns[] = $column['COLUMN_NAME'];
                    }
                    $syncObject->setIndex(true);
                    $syncObject->setErrorSummary("<b>Index</b> <samp>( " . implode(", ", $columns) . " )</samp> doesn't set. ");
                }
            } else {
                $columns = [];
                foreach ($sourceIndexCols as $column) {
                    $columns[] = $column['COLUMN_NAME'];
                }
                $syncObject->setIndex(true);
                $syncObject->setErrorSummary("<b>Index</b> <samp>( " . implode(", ", $columns) . " )</samp> doesn't set. ");
            }
        }

        if (ArrayHelper::getValue($source, 'extra.column.total') !== ArrayHelper::getValue($destination, 'extra.column.total')) {
            $syncObject->setCol(true);
            $syncObject->setNumberOfCols(ArrayHelper::getValue($source, 'extra.column.total'));
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>Columns</b> doesn't match, founded ( " . ArrayHelper::getValue($source, 'extra.column.total') . " ) Diff: ( " . (ArrayHelper::getValue($source, 'extra.column.total') - ArrayHelper::getValue($destination, 'extra.column.total')) . " )");

            $missingCol = [];
            $sourceColInfo = ArrayHelper::getValue($source, 'extra.column.info');
            $destinationColInfo = ArrayHelper::getValue($destination, 'extra.column.info');
            foreach ($sourceColInfo as $colInfo) {
                $colName = trim($colInfo['COLUMN_NAME']); // country_code
                $isColMatch = false;
                foreach ($destinationColInfo as $destColInfo) {
                    if(trim($colInfo['COLUMN_NAME'])===trim($destColInfo['COLUMN_NAME'])){
                        $isColMatch = true;
                    }
                }
                if (!$isColMatch) {
                    $missingCol[] = $colName;
                }
            }
            $syncObject->setErrorSummary("<b> - Missing </b> ( " . implode(", ", $missingCol) . " )");
        }

        if (ArrayHelper::getValue($source, 'extra.row.total') !== ArrayHelper::getValue($destination, 'extra.row.total')) {
            $syncObject->setRows(true);
            $syncObject->setNumberOfRows(ArrayHelper::getValue($source, 'extra.row.total'));
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>Rows</b> doesn't match, founded ( " . ArrayHelper::getValue($source, 'extra.row.total') . " ) Diff: ( " . (ArrayHelper::getValue($source, 'extra.row.total') - ArrayHelper::getValue($destination, 'extra.row.total')) . " )");
        }


        dd($source);
        dd($syncObject);
        die();


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

    public static function combinedData($sourceData, $destinationData, $sourceHost, $destinationHost): SyncObject
    {

        //dd($sourceData); die();

        $map = [];
        foreach ($sourceData as $table => $source) {
            if (isset($destinationData[$table])) {
                $traget = $destinationData[$table];
                $map[] = self::singularSyncObject($table, $source, $traget, $sourceHost, $destinationHost);
            } else {
                $syncObject = new SyncObject();
                $syncObject->setErrorSummary("<b>${table}</b> table doesn't exist.");
                $map[] = $syncObject;
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