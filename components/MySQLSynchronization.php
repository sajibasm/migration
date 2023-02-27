<?php

namespace app\components;

use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use stdClass;
use Yii;
use yii\db\Connection;
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
            $ignoreDatabase = ['information_schema', 'innodb', 'mysql', 'performance_schema', 'sys', 'tmp'];
            $syncHostDB = new SyncHostDb();
            foreach ($database as $name) {
                if (!in_array($name['Database'], $ignoreDatabase)) {
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

    public static function getTotalCount($db)
    {
        $tables = $db->createCommand('SHOW TABLES;')->queryAll();
        return count($tables);
    }

    public static function getTableInfo(Connection $connection, string $database, $clearCache = false)
    {
        $key = md5("getTableInfo" . $connection->dsn);
        if ($clearCache) {
            return Yii::$app->getCache()->delete($key);
        } else {
            $data = Yii::$app->getCache()->get($key);
            if ($data === false) {
                $data = $connection->createCommand("SELECT * FROM  information_schema.TABLES WHERE  TABLE_SCHEMA = '${database}';")->queryAll();
                Yii::$app->getCache()->set($key, $data, 180);
            }
            return $data;
        }

    }

    public static function getColumnConstraint(Connection $connection, string $database, $clearCache = false)
    {
        $key = md5("getColumnConstraint" . $connection->dsn);
        if ($clearCache) {
            return Yii::$app->getCache()->delete($key);
        } else {
            $data = Yii::$app->getCache()->get($key);
            if ($data === false) {
                $data = $connection->createCommand("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = '${database}';  ")->queryAll();
                Yii::$app->getCache()->set($key, $data, 180);
            }
            return $data;
        }
    }

    public static function getForeignKeyInfo(Connection $connection, string $database, $clearCache = false)
    {
        $key = md5("getForeignKeyInfo" . $connection->dsn);
        if ($clearCache) {
            return Yii::$app->getCache()->delete($key);
        } else {
            $data = Yii::$app->getCache()->get($key);
            if ($data === false) {
                $data = $connection->createCommand("SELECT CONCAT(table_name, '.', column_name) AS 'foreign_key', CONCAT(referenced_table_name, '.', referenced_column_name) AS 'references', constraint_name AS 'constraint_name' FROM information_schema.key_column_usage WHERE referenced_table_name IS NOT NULL AND table_schema = '${database}';")->queryAll();
                Yii::$app->getCache()->set($key, $data, 180);
            }
            return $data;
        }

    }

    public static function getIndexKeyInfo(Connection $connection, string $database, $clearCache = false)
    {
        $key = md5("getIndexKeyInfo" . $connection->dsn);
        if ($clearCache) {
            return Yii::$app->getCache()->delete($key);
        } else {
            $data = Yii::$app->getCache()->get($key);
            if ($data === false) {
                $data = $connection->createCommand("SELECT DISTINCT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '${database}';")->queryAll();
                Yii::$app->getCache()->set($key, $data, 180);
            }
            return $data;
        }
    }


    public static function getTableStatics($connection, $database)
    {
        $statData = [];
        $tableInfos = self::getTableInfo($connection, $database);
        $columnConstraints = self::getColumnConstraint($connection, $database);
        $foreignKeyInfo = self::getForeignKeyInfo($connection, $database);
        $indexKeyInfo = self::getIndexKeyInfo($connection, $database);

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

    /**
     * @param $table
     * @param $sourceTable
     * @param $targetTable
     * @param $sourceHost
     * @param $targetHost
     * @return SyncObject
     * @throws \Exception
     */
    private static function singularSyncObject($table, $sourceTable, $targetTable, $sourceHost, $targetHost): SyncObject
    {
        $syncObject = new SyncObject();
        $syncObject->setTable($table);
        $syncObject->setSourceHost($sourceHost);
        $syncObject->setDestinationHost($targetHost);
        $syncObject->setExtra(ArrayHelper::getValue($sourceTable, 'extra'));

        //Check Engine type
        if (ArrayHelper::getValue($sourceTable, 'engine')) {
            if (ArrayHelper::getValue($targetTable, 'engine')) {
                if ((ArrayHelper::getValue($sourceTable, 'engineType') !== ArrayHelper::getValue($targetTable, 'engineType'))) {
                    $syncObject->setEngine(true);
                    $syncObject->setEngineType(ArrayHelper::getValue($sourceTable, 'engineType'));
                    $syncObject->setError(true);
                    $syncObject->setErrorSummary("<b>Engine</b> (" . ArrayHelper::getValue($sourceTable, 'engineType') . ") doesn't match ");
                }
            } else {
                $syncObject->setEngine(true);
                $syncObject->setEngineType(ArrayHelper::getValue($sourceTable, 'engineType'));
                $syncObject->setError(true);
                $syncObject->setErrorSummary("<b>Engine</b> doesn't set.");
            }
        }

        //Find all primary keys
        if (ArrayHelper::getValue($sourceTable, 'primary')) {

            $sourcePriCols = ArrayHelper::getValue($sourceTable, 'extra.column.primary');

            if (!ArrayHelper::getValue($targetTable, 'primary') && $sourcePriCols) {
                $syncObject->setPrimary(true);
                $primaryCols = [];
                foreach ($sourcePriCols as $sourcePrimaryCol) {
                    $primaryCols[] = ArrayHelper::getValue($sourcePrimaryCol, 'COLUMN_NAME') . "[" . ArrayHelper::getValue($sourcePrimaryCol, 'DATA_TYPE') . "]";
                }
                $syncObject->setErrorSummary("<b>Primary Key</b> doesn't set( " . implode(", ", $primaryCols) . " )");
            } else {
                if ($sourcePriCols) {
                    $targetPriCols = ArrayHelper::getValue($targetTable, 'extra.column.primary');
                    //dd($sourcePrimaryCols);dd($targetPrimaryCols);die();
                    if ($targetPriCols) {
                        foreach ($sourcePriCols as $sourcePriCol) {
                            foreach ($targetPriCols as $targetPriCol) {
                                if ((ArrayHelper::getValue($sourcePriCol, 'COLUMN_NAME') === ArrayHelper::getValue($targetPriCol, 'COLUMN_NAME')) &&
                                    (ArrayHelper::getValue($sourcePriCol, 'COLUMN_KEY') === 'PRI') && (ArrayHelper::getValue($targetPriCol, 'COLUMN_KEY') === 'PRI')
                                ) {
                                    ArrayHelper::remove($sourcePriCol, 'TABLE_SCHEMA');
                                    ArrayHelper::remove($targetPriCol, 'TABLE_SCHEMA');
                                    $primaryColumnDiff = array_diff($sourcePriCol, $targetPriCol);
                                    //dd($primaryColumnDiff, $sourcePriCol, $targetPriCol); die();
                                    if ($primaryColumnDiff) {

                                        $syncObject->setPrimary(true);
                                        $syncObject->setPrimaryKeys(ArrayHelper::getValue($sourcePriCol, 'DATA_TYPE') ?: []);

                                        if (ArrayHelper::getValue($primaryColumnDiff, 'EXTRA')) {
                                            if (ArrayHelper::getValue($primaryColumnDiff, 'EXTRA') === 'auto_increment') {
                                                $syncObject->setAutoIncrement(true);
                                                $syncObject->setAutoIncrementKeys(ArrayHelper::getValue($sourcePriCol, 'COLUMN_NAME'));
                                                $syncObject->setErrorSummary("<b>Auto Increment</b> (" . ArrayHelper::getValue($sourceTable, 'extra.column.autoIncrement.COLUMN_NAME') . ") doesn't set. ");
                                            }
                                        }

                                        if (ArrayHelper::getValue($primaryColumnDiff, 'DATA_TYPE')) {
                                            $syncObject->setErrorSummary("<b> - </b>  set(" . ArrayHelper::getValue($sourcePriCol, 'COLUMN_NAME') . "[" . ArrayHelper::getValue($sourcePriCol, 'DATA_TYPE') . "]) modified (" . ArrayHelper::getValue($targetPriCol, 'COLUMN_NAME') . "[" . ArrayHelper::getValue($targetPriCol, 'DATA_TYPE') . "])");
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $syncObject->setPrimary(true);
                        $primaryCols = [];
                        foreach ($sourcePriCols as $sourcePrimaryCol) {
                            $primaryCols[] = ArrayHelper::getValue($sourcePrimaryCol, 'COLUMN_NAME') . "[" . ArrayHelper::getValue($sourcePrimaryCol, 'DATA_TYPE') . "]";
                        }
                        $syncObject->setErrorSummary("<b>Primary Key</b> doesn't set( " . implode(", ", $primaryCols) . " )");
                    }
                }
            }
        }

//        //find Auto Increment
//        if (ArrayHelper::getValue($sourceTable, 'autoIncrement') && !ArrayHelper::getValue($targetTable, 'autoIncrement')) {
//            $syncObject->setAutoIncrement(true);
//            $syncObject->setAutoIncrementKeys(ArrayHelper::getValue($sourceTable, 'extra.column.autoIncrement.COLUMN_NAME'));
//            $syncObject->setErrorSummary("<b>Auto Increment</b> (" . ArrayHelper::getValue($sourceTable, 'extra.column.autoIncrement.COLUMN_NAME') . ") doesn't set. ");
//        }

        //check unique columns
        if (ArrayHelper::getValue($sourceTable, 'unique')) {
            $sourceUniqueInfo = ArrayHelper::getValue($sourceTable, 'extra.column.unique');
            $destinationUniqueInfo = ArrayHelper::getValue($targetTable, 'extra.column.unique');
            if (ArrayHelper::getValue($targetTable, 'unique')) {
                $uniqueMissingCols = [];
                foreach ($sourceUniqueInfo as $srcColumn) {
                    $isFound = false;
                    foreach ($destinationUniqueInfo as $desColumn) {
                        if ($desColumn['COLUMN_NAME'] === $srcColumn['COLUMN_NAME']) {
                            $isFound = true;
                        }
                    }
                    if (!$isFound) {
                        $uniqueMissingCols[] = $srcColumn['COLUMN_NAME'];
                    }
                }
                if ($uniqueMissingCols) {
                    $syncObject->setUnique(true);
                    $syncObject->setUniqueKeys($uniqueMissingCols);
                    $syncObject->setError(true);
                    $syncObject->setErrorSummary("<b>Unique Key</b> ( " . implode(", ", $uniqueMissingCols) . " ) doesn't set. ");
                }
            } else {
                $uniqueColumns = [];
                foreach ($sourceUniqueInfo as $column) {
                    $uniqueColumns[] = $column['COLUMN_NAME'];
                }
                $syncObject->setUnique(true);
                $syncObject->setUniqueKeys($uniqueColumns);
                $syncObject->setError(true);
                $syncObject->setErrorSummary("<b>Unique Key</b> ( " . implode(", ", $uniqueColumns) . " ) doesn't set. ");
            }
        }

        if (ArrayHelper::getValue($sourceTable, 'foreign')) {
            $sourceForeignInfo = ArrayHelper::getValue($sourceTable, 'extra.column.foreign');
            $destinationForeignInfo = ArrayHelper::getValue($targetTable, 'extra.column.foreign');
            if (ArrayHelper::getValue($targetTable, 'foreign')) {
                $foreignMissingCols = [];
                foreach ($sourceForeignInfo as $srcColumn) {
                    $isFound = false;
                    foreach ($destinationForeignInfo as $desColumn) {
                        if (ArrayHelper::getValue($srcColumn, 'foreign_key') === ArrayHelper::getValue($desColumn, 'foreign_key')) {
                            $isFound = true;
                        }
                    }
                    if (!$isFound) {
                        $foreignMissingCols[] = ArrayHelper::getValue($srcColumn, 'foreign_key');
                    }
                }
                if ($foreignMissingCols) {
                    $syncObject->setForeign(true);
                    $syncObject->setUniqueKeys($foreignMissingCols);
                    $syncObject->setError(true);
                    $syncObject->setErrorSummary("<b>Foreign Key</b> ( " . implode(", ", $foreignMissingCols) . " ) doesn't set. ");
                }
            } else {
                $foreignColumns = [];
                foreach ($sourceForeignInfo as $column) {
                    $foreignColumns[] = ArrayHelper::getValue($column, 'foreign_key');
                }
                $syncObject->setForeign(true);
                $syncObject->setForeignKeys($foreignColumns);
                $syncObject->setError(true);
                $syncObject->setErrorSummary("<b>Foreign Key</b> ( " . implode(", ", $foreignColumns) . " ) doesn't set. ");
            }
        }

        if (ArrayHelper::getValue($sourceTable, 'index')) {
            $sourceIndexCols = ArrayHelper::getValue($sourceTable, 'extra.column.index');
            $targetIndexCols = ArrayHelper::getValue($targetTable, 'extra.column.index');

            if (ArrayHelper::getValue($targetTable, 'index') && $targetIndexCols) {
                $IndexMissingCols = [];
                foreach ($sourceIndexCols as $sourceIndexCol) {
                    $isFound = false;
                    foreach ($targetIndexCols as $targetColumn) {
                        if ($targetColumn['COLUMN_NAME'] === $sourceIndexCol['COLUMN_NAME']) {
                            $isFound = true;
                        }
                    }
                    if (!$isFound) {
                        $IndexMissingCols[] = $sourceIndexCol['COLUMN_NAME'];
                    }
                }

                if ($IndexMissingCols) {
                    $syncObject->setIndex(true);
                    $syncObject->setIndexKeys($IndexMissingCols ?: []);
                    $syncObject->setError(true);
                    $syncObject->setErrorSummary("<b>Index Key</b> ( " . implode(", ", $IndexMissingCols) . " ) doesn't set. ");
                }
            } else {
                $indexColumns = [];
                foreach ($sourceIndexCols as $sourceIndexColumn) {
                    $indexColumns[] = $sourceIndexColumn['COLUMN_NAME'];
                }
                $syncObject->setIndex(true);
                $syncObject->setIndexKeys($indexColumns ?: []);
                $syncObject->setError(true);
                $syncObject->setErrorSummary("<b>Index Key</b> ( " . implode(", ", $indexColumns) . " ) doesn't set. ");
            }
        }

        if (ArrayHelper::getValue($sourceTable, 'extra.column.total') !== ArrayHelper::getValue($targetTable, 'extra.column.total')) {
            $syncObject->setCol(true);
            $syncObject->setNumberOfCols(ArrayHelper::getValue($sourceTable, 'extra.column.total'));
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>Columns</b> doesn't match, original ( " . ArrayHelper::getValue($sourceTable, 'extra.column.total') . " ) Diff: ( " . (ArrayHelper::getValue($sourceTable, 'extra.column.total') - ArrayHelper::getValue($targetTable, 'extra.column.total')) . " )");

            $missingCol = [];
            $sourceColInfos = ArrayHelper::getValue($sourceTable, 'extra.column.info');
            $targetColInfos = ArrayHelper::getValue($targetTable, 'extra.column.info');
            foreach ($sourceColInfos as $sourceColInfo) {
                $colName = trim($sourceColInfo['COLUMN_NAME']); // country_code
                $isColMatch = false;
                foreach ($targetColInfos as $targetColInfo) {
                    if (trim($sourceColInfo['COLUMN_NAME']) === trim($targetColInfo['COLUMN_NAME'])) {
                        $isColMatch = true;
                    }
                }
                if (!$isColMatch) {
                    $missingCol[] = $colName;
                }
            }
            $syncObject->setErrorSummary("<b> - Absent </b> ( " . implode(", ", $missingCol) . " )");
        }

        if (ArrayHelper::getValue($sourceTable, 'extra.row.total') !== ArrayHelper::getValue($targetTable, 'extra.row.total')) {
            $syncObject->setRows(true);
            $syncObject->setNumberOfRows(ArrayHelper::getValue($sourceTable, 'extra.row.total'));
            $syncObject->setError(true);
            $syncObject->setErrorSummary("<b>Rows</b> doesn't match, original ( " . ArrayHelper::getValue($sourceTable, 'extra.row.total') . " ) Diff: ( " . (ArrayHelper::getValue($sourceTable, 'extra.row.total') - ArrayHelper::getValue($targetTable, 'extra.row.total')) . " )");
        }

        $sourceExtraInfos = ArrayHelper::getValue($sourceTable, 'extra.column.info');
        $targetExtraInfos = ArrayHelper::getValue($targetTable, 'extra.column.info');
        if ($sourceExtraInfos && $targetExtraInfos) {
            foreach ($sourceExtraInfos as $sourceExtraInfo) {
                $colName = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_NAME'); // country_code
                $colDefault = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_DEFAULT');  //
                $colIsNullable = ArrayHelper::getValue($sourceExtraInfo, 'IS_NULLABLE');  //
                $colDataType = ArrayHelper::getValue($sourceExtraInfo, 'DATA_TYPE'); // varchar, char, int, 'timestamp', '
                $colKey = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_KEY');  // PRI, UNI, MUL, ''
                $colCharMaxLength = ArrayHelper::getValue($sourceExtraInfo, 'CHARACTER_MAXIMUM_LENGTH'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION
                $colNumberPrecision = ArrayHelper::getValue($sourceExtraInfo, 'NUMERIC_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
                $colDatetimePrecision = ArrayHelper::getValue($sourceExtraInfo, 'DATETIME_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
                $colType = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_TYPE');  // int, varchar , 'CURRENT_TIMESTAMP'
                $colComment = ArrayHelper::getValue($sourceExtraInfo, 'COLUMN_COMMENT');  //
                $colExtra = ArrayHelper::getValue($sourceExtraInfo, 'EXTRA');  // 1, 'Running', 'CURRENT_TIMESTAMP'
                $colCollationName = ArrayHelper::getValue($sourceExtraInfo, 'COLLATION_NAME');  // 1, 'Running', 'CURRENT_TIMESTAMP'

                foreach ($targetExtraInfos as $targetExtraInfo) {
                    $destColName = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_NAME'); // country_code
                    $destColDefault = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_DEFAULT');  //
                    $destColIsNullable = ArrayHelper::getValue($targetExtraInfo, 'IS_NULLABLE');  //
                    $destColDataType = ArrayHelper::getValue($targetExtraInfo, 'DATA_TYPE'); // varchar, char, int, 'timestamp', '
                    $destColKey = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_KEY');  // PRI, UNI, MUL, ''
                    $destColCharMaxLength = ArrayHelper::getValue($targetExtraInfo, 'CHARACTER_MAXIMUM_LENGTH'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION
                    $destColNumberPrecision = ArrayHelper::getValue($targetExtraInfo, 'NUMERIC_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
                    $destColDatetimePrecision = ArrayHelper::getValue($targetExtraInfo, 'DATETIME_PRECISION'); // 255=>varchar, 2=>char, int=>'' ->check NUMERIC_PRECISION, 'tinyint'
                    $destColType = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_TYPE');  // int, varchar , 'CURRENT_TIMESTAMP'
                    $destColComment = ArrayHelper::getValue($targetExtraInfo, 'COLUMN_COMMENT');  //
                    $destColExtra = ArrayHelper::getValue($targetExtraInfo, 'EXTRA');  // 1, 'Running', 'CURRENT_TIMESTAMP'
                    $destColCollationName = ArrayHelper::getValue($targetExtraInfo, 'COLLATION_NAME');  // 1, 'Running', 'CURRENT_TIMESTAMP'

                    if ($destColName === $colName) {

                        $colAttributeError = [];

                        if ($colDataType !== $destColDataType) {
                            $colAttributeError[] = "&ensp;-Type doesn't match, original( <b>${colDataType}</b> ) modified(<b>( ${destColDataType}</b> )";
                        }

                        if (!empty($colCharMaxLength) && ($colCharMaxLength !== $destColCharMaxLength)) {
                            $colAttributeError[] = "&ensp;-Length doesn't match, original( <b>${colCharMaxLength}</b> ) modified( <b>${destColCharMaxLength}</b> )";
                        }

                        if (!empty($colNumberPrecision) && ($colNumberPrecision !== $destColNumberPrecision)) {
                            $colAttributeError[] = "&ensp;-Length doesn't match, original (<b>${colNumberPrecision}</b>) modified (<b>${destColNumberPrecision}</b>)";
                        }

                        if (!empty($colDatetimePrecision) && ($colDatetimePrecision !== $destColDatetimePrecision)) {
                        }

                        if (!empty($destColDefault) && ($colDefault !== $destColDefault)) {
                            $colAttributeError[] = "&ensp;-Default value doesn't match, original (<b>${colDefault}</b>) modified (<b>${destColDefault}</b>)";
                        }

                        if (!empty($colCollationName) && ($colCollationName !== $destColCollationName)) {
                            $colAttributeError[] = "&ensp;-Collation doesn't match, original (<b>${colCollationName}</b>) modified (<b>${destColCollationName}</b>)";
                        }

                        if (!empty($colComment) && ($colComment !== $destColComment)) {
                            $colAttributeError[] = "&ensp;-Comment doesn't match, original (<b>${colComment}</b>) modified (<b>${destColComment}</b>)";
                        }

                        if (count($colAttributeError) > 0) {
                            $syncObject->setError(true);
                            $syncObject->setCol(true);
                            $syncObject->setErrorSummary("<b>Column</b> <u>${colName}</u> attributes erros:");
                            $syncObject->setErrorSummary($colAttributeError);

                        }
                    }
                }
            }
        }
        //["<b>Index Key</b> ( status ) doesn't set. ","<b>Columns</b> doesn't match, original ( 4 ) Diff: ( 1 )","<b> - Absent </b> ( status )","<b>Column</b> <u>name</u> attributes erros:",["&ensp;Comment doesn't match, original (<b>Add Comments</b>) modified (<b></b>)"]]

        //dd($syncObject);
        //die($syncObject->getAutoIncrementKeys());
        return $syncObject;
    }


    public static function mapping($sourceData, $destinationData, $sourceHost, $destinationHost)
    {
        $map = [];
        foreach ($sourceData as $table => $sourceTable) {
            if (isset($destinationData[$table])) {
                $targetTable = $destinationData[$table];
                $map[] = self::singularSyncObject($table, $sourceTable, $targetTable, $sourceHost, $destinationHost);
            } else {
                $syncObject = new SyncObject();
                $syncObject->setTable($table);
                $syncObject->setEngine(true);
                $syncObject->setAutoIncrement(true);
                $syncObject->setPrimary(true);
                $syncObject->setForeign(true);
                $syncObject->setUnique(true);
                $syncObject->setIndex(true);
                $syncObject->setCol(true);
                $syncObject->setRows(true);
                $syncObject->setError(true);
                $syncObject->setExtra(ArrayHelper::getValue($sourceTable, 'extra'));
                $syncObject->setErrorSummary("<b>${table}</b> table doesn't exist.");
                $map[] = $syncObject;
            }
        }
        return $map;
    }

    public static function process(SyncHostDb $source, SyncHostDb $destination)
    {
        $sourceConfigModel = SyncConfig::findOne(['type' => $source->type]);
        $destinationConfigModel = SyncConfig::findOne(['type' => $destination->type]);

        $sourceConnection = DynamicConnection::createConnection($sourceConfigModel, $source->dbname);
        $destinationConnection = DynamicConnection::createConnection($destinationConfigModel, $destination->dbname);

        $sourceData = MySQLSynchronization::getTableStatics($sourceConnection, $source->dbname);
        $DestinationData = MySQLSynchronization::getTableStatics($destinationConnection, $destination->dbname);

        $mappingData = MySQLSynchronization::mapping($sourceData, $DestinationData, $sourceConnection->dsn, $destinationConnection->dsn);

        if ($mappingData) {
            $rows = [];
            foreach ($mappingData as $key => $syncObject) {
                $rows[] = [
                    null,
                    $source->id,
                    $destination->id,
                    $syncObject->table,
                    (int)!$syncObject->engine,
                    (string)$syncObject->engineType,
                    (int)!$syncObject->autoIncrement,
                    $syncObject->autoIncrementKeys,
                    (int)!$syncObject->primary,
                    Json::encode($syncObject->primaryKeys),
                    (int)!$syncObject->foreign,
                    Json::encode($syncObject->foreignKeys),
                    (int)!$syncObject->unique,
                    Json::encode($syncObject->uniqueKeys),
                    (int)!$syncObject->index,
                    Json::encode($syncObject->indexKeys),
                    (int)!$syncObject->col,
                    (int)!$syncObject->numberOfCols,
                    (int)!$syncObject->rows,
                    (int)!$syncObject->numberOfRows,
                    Json::encode($syncObject->extra),
                    (int)!$syncObject->error,
                    Json::encode($syncObject->errorSummary),
                    SyncTable::STATUS_PULL,
                    date('Y-m-d h:i:s'),
                    date('Y-m-d h:i:s')
                ];
            }

            Yii::$app->db->createCommand()->batchInsert(SyncTable::tableName(), [
                'id', 'sourceDb', 'destinationDb', 'tableName', 'isEngine', 'engineType',
                'autoIncrement', 'autoIncrementKey', 'isPrimary', 'primaryKeys', 'isForeign', 'foreignKeys',
                'isUnique', 'uniqueKeys', 'isIndex', 'indexKeys', 'isCols', 'numberOfCols', 'isRows',
                'numberOfRows', 'extra', 'isError', 'errorSummary', 'status', 'createdAt', 'processedAt'
            ], $rows)->execute();
        }
    }
}