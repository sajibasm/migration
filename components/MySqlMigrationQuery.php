<?php

namespace app\components;

use app\models\TableCompare;
use Yii;
use yii\helpers\Json;

class MySqlMigrationQuery
{

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

        $data = [];
        $data['id'] = $table;
        $data['table'] = $table;
        $data['engine'] = true;
        $data['engineType'] = $prod['engine'];
        $data['primary'] = true;
        $data['primaryKeys'] = "";
        $data['autoIncrement'] = true;
        $data['autoIncrementKey'] = '';
        $data['unique'] = true;
        $data['uniqueKeys'] = "";
        $data['index'] = true;
        $data['indexKeys'] = "";
        $data['rows'] = true;
        $data['cols'] = true;
        $data['maxType'] = false;
        $data['maxId'] = true;
        $data['colInfo'] = $prod['colInfo'];
        $data['error'] = false;
        $data['errorSummary'] = '';

        //dd($prod);die();

        if ($prod['engine'] !== $migrationData['engine']) {
            $data['engine'] = false;
            $data['engineType'] = $prod['engine'];
            $data['errorSummary'] .= "- <b>Engine</b> doesn't match" . "<br>";
        }

        if ($prod['autoIncrement'] !== $migrationData['autoIncrement']) {
            $data['autoIncrement'] = false;
            $data['autoIncrementKey'] = $prod['autoIncrement'];
            $data['error'] = true;
            $data['errorSummary'] .= "- <b> Auto Increment <samp>(" . $prod['autoIncrement'] . ")<samp></b> doesn't set. " . "<br>";
        }

        if (!empty(array_diff($prod['primary'], $migrationData['primary']))) {
            $data['primary'] = false;
            $data['error'] = true;
            $arrayDiff = array_diff($prod['primary'], $migrationData['primary']);
            $data['primaryKeys'] = $arrayDiff;
            if (count($arrayDiff) === 1) {
                $arrayDiff = '["' . $arrayDiff[0] . '"]';
            } else {
                $arrayDiff = json_encode($arrayDiff);
            }
            $data['errorSummary'] .= "- <b> Primary Key</b> doesn't match columns <samp>" . $arrayDiff . "</samp><br>";
        }

        if (!empty(array_diff($prod['unique'], $migrationData['unique']))) {
            $data['unique'] = false;
            $data['error'] = true;
            $arrayDiff = array_diff($prod['unique'], $migrationData['unique']);
            $data['uniqueKeys'] = $arrayDiff;
            if (count($arrayDiff) === 1) {
                $arrayDiff = '["' . $arrayDiff[0] . '"]';
            } else {
                $arrayDiff = json_encode($arrayDiff);
            }
            $data['errorSummary'] .= "- <b> Unique Key</b> doesn't match columns <samp>" . $arrayDiff . "</samp><br>";
        }

        if (!empty(array_diff($prod['index'], $migrationData['index']))) {
            $data['index'] = false;
            $data['error'] = true;
            $arrayDiff = array_diff($prod['index'], $migrationData['index']);
            if (count($arrayDiff) > 1) {
                $arrayDiff = json_encode($arrayDiff);
            } else {
                $arrayDiff = '[ "' . $arrayDiff[0] . '" ]';
            }
            $data['indexKeys'] = $arrayDiff;
            $data['errorSummary'] .= "- <b>Index Key</b> doesn't match <samp>" . $arrayDiff . "</samp><br>";
        }

        if ($prod['cols'] !== $migrationData['cols']) {
            $data['cols'] = false;
            $data['error'] = true;
            $data['errorSummary'] .= "- <b> Columns </b> doesn't match founded: <b>" . $migrationData['cols'] . "</b> out of <b>" . $prod['cols'] . "</b><br>";

            $missingCol = [];

            foreach ($prod['colInfo'] as $colInfo) {
                $colKey = trim($colInfo['COLUMN_KEY']); // PRI, UNI, MUL, ''
                if (empty($colKey)) {
                    $colName = trim($colInfo['COLUMN_NAME']); // country_code
                    $isColMatch = false;
                    foreach ($migrationData['colInfo'] as $migColInfo) {
                        $migColName = trim($migColInfo['COLUMN_NAME']);
                        if (!$isColMatch) {
                            $beforeCol = $migColName;
                        }
                        if ($migColName === $colName) {
                            $isColMatch = true;
                        }
                    }
                    if (!$isColMatch) {
                        $data['error'] = true;
                        $missingCol[] = $colName;
                    }
                }
            }

            if (count($missingCol) > 0) {
                $data['error'] = true;
                if (count($missingCol) > 1) {
                    $missingCol = json_encode($missingCol);
                } else {
                    $missingCol = '[ "' . $missingCol[0] . '" ]';
                }
                $data['errorSummary'] .= "- <samp> <b> " . $missingCol . "</b> colmumns doesn't Found</samp>.<br>";
            }
        }

        if ($prod['rows'] !== $migrationData['rows']) {
            $data['rows'] = false;
            $data['error'] = true;
            $data['errorSummary'] .= '- <b>Rows</b> ' . $prod['rows'] . ' Founded ' . $migrationData['rows'] . '<b><i> Diff</i></b>:  ' . ($prod['rows'] - $migrationData['rows']) . ' rows' . "<br>";
        }

        if ($prod['maxId'] !== $migrationData['maxId'] && ($prod['primary'] === $migrationData['primary'])) {
            $data['maxId'] = false;
            $data['error'] = true;
            $data['errorSummary'] .= "- <b>MaxId</b> doesn't match columnsed: " . $prod['maxId'] . ' Founded ' . $migrationData['maxId'];
        }

        return $data;
    }

    public static function combinedData($prodData, $migrationsData)
    {
        $map = [];
        foreach ($prodData as $table => $prod) {
            if (isset($migrationsData[$table])) {
                $migrationData = $migrationsData[$table];
                $object = (object)self::getSingular($table, $prod, $migrationData);
                $map[] = $object;
            }
        }

        return (object)$map;
    }

    public static function bulkInsert()
    {
        $prodData = MySqlMigrationQuery::getStatics(Yii::$app->prodDb);
        $migrateData = MySqlMigrationQuery::getStatics(Yii::$app->migrateDb);
        $mappingData = MySqlMigrationQuery::combinedData($prodData, $migrateData);

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
                $tableCompare->maxType = (string)$object->maxId;
                $tableCompare->maxValue = (string)$object->maxId;
                $tableCompare->cols = (int)$object->cols;
                $tableCompare->rows = (int)$object->rows;
                $tableCompare->columnStatics = Json::encode($object->colInfo);
                $tableCompare->isError = (int)$object->error;
                $tableCompare->errorSummary = $object->errorSummary;
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