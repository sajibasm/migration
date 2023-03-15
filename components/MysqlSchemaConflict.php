<?php

namespace app\components;


use app\jobs\SchemeInfoJob;
use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use Exception;
use Yii;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;


class MysqlSchemaConflict
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

    protected static function getEngine(Connection &$connection, string $database, $table, $clearCache = false)
    {
        if ($clearCache) {
            return Yii::$app->getCache()->flush();
        }

        $key = md5(trim($table) . "_engine_info" . $database);
        $infoSchemaData = Yii::$app->getCache()->get($key);
        if ($infoSchemaData === false) {
            //echo "\nEngine Data from SQL\n";
            $engineData = $connection->createCommand("SELECT * FROM  information_schema.TABLES WHERE  TABLE_SCHEMA = '${database}';")->queryAll();
            foreach ($engineData as $row) {
                //echo '\n'.$row['TABLE_NAME'] .'\n';
                if ($row['TABLE_NAME'] === $table) {
                    $infoSchemaData = $row;
                }
                Yii::$app->getCache()->set(md5(trim($row['TABLE_NAME']) . "_engine_info" . $database), $row, 180);
            }
        } else {
            //echo "\nEngine Data from Cache\n";
        }

        return new Engine($infoSchemaData);
    }

    protected static function getIndex(Connection &$connection, string $database, $table, $clearCache = false)
    {
        if ($clearCache) {
            return Yii::$app->getCache()->flush();
        }

        $key = md5(trim($table) . "_index_info_cache" . $database);
        $infoSchemaData = Yii::$app->getCache()->get($key) ?: [];

        if (empty($infoSchemaData)) {
            //echo "\nIndex Data from SQL\n";
            $indexData = $connection->createCommand("SELECT DISTINCT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '${database}';")->queryAll();
            foreach ($indexData as $row) {
                if ($row['TABLE_NAME'] === $table && $row['INDEX_NAME'] !== 'PRIMARY') {
                    $infoSchemaData[] = ['index' => $row['INDEX_NAME'], 'key' => $row['COLUMN_NAME']];
                }
            }
            Yii::$app->getCache()->set(md5(trim($row['TABLE_NAME']) . "_index_info_cache" . $database), $infoSchemaData, 180);
        }
        return $infoSchemaData;
    }

    protected static function getColumnCollation(Connection &$connection, string $database, $table, $clearCache = false)
    {
        if ($clearCache) {
            return Yii::$app->getCache()->flush();
        }

        $key = md5(trim($table) . "_table_column_collation_cache" . $database);
        $infoSchemaData = Yii::$app->getCache()->get($key) ?: [];
        if (empty($infoSchemaData)) {
            //echo "\nIndex Data from SQL\n";
            $columnCollationData = $connection->createCommand("SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '${database}' AND TABLE_NAME='${table}';")->queryAll();
            foreach ($columnCollationData as $row) {
                if ($row['TABLE_NAME'] === $table) {
                    if (isset($infoSchemaData[$row['COLUMN_NAME']])) {
                        $infoSchemaData[$row['COLUMN_NAME']] = [
                            "COLUMN_NAME" => $row['COLUMN_NAME'],
                            'COLLATION' => $row['COLLATION_NAME'] ?: null,
                        ];
                    } else {
                        $infoSchemaData[$row['COLUMN_NAME']] = [
                            "COLUMN_NAME" => $row['COLUMN_NAME'],
                            'COLLATION' => $row['COLLATION_NAME'] ?: null,
                        ];
                    }
                }
            }
        }

        Yii::$app->getCache()->set($key, $infoSchemaData, 180);
        return $infoSchemaData;

    }

    protected static function getTotalRows(Connection &$connection, string $database, $table)
    {
        $data = $connection->createCommand("SELECT COUNT(*) as total FROM " . $database . "." . $table . ";")->queryOne();
        if ($data) {
            return $data['total'];
        }
        return 0;
    }

    /**
     * @param $sourceOrTargetSchema
     * @param Connection $connection
     * @param string $database
     * @param $table
     * @param $clearCache
     * @return SchemaObject|bool
     * @throws \yii\db\Exception
     */
    public static function getTableInfo(&$sourceOrTargetSchema, Connection &$connection, string $database, $table, $clearCache = false)
    {
        if ($clearCache) {
            echo "..";
            return Yii::$app->getCache()->flush();
        }

        echo "..";
        $schema = new SchemaObject($sourceOrTargetSchema);
        echo "..";
        $schema->setEngine(self::getEngine($connection, $database, $table));
        echo "..";
        $schema->setIndex(self::getIndex($connection, $database, $table));
        $schema->setColumnCollations(self::getColumnCollation($connection, $database, $table));
        $schema->setTotalRows(self::getTotalRows($connection, $database, $table));
        echo "..";
        return $schema;
    }

    public
    static function saveTableMetaQueue(SyncHostDb $source, SyncHostDb $target)
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

            Yii::$app->getCache()->flush();

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

    public static function singularTableInfo(SyncTable &$syncTableModel, Connection $sourceConnection, Connection $targetConnection, SchemaObject $sourceSchema, SchemaObject $targetSchema)
    {
        try {

            $errorSummary = [];
            $syncTableModel->tableName = $sourceSchema->fullName;
            $syncTableModel->isEngine = 1;
            $syncTableModel->autoIncrement = 1;
            $syncTableModel->isPrimary = 1;
            $syncTableModel->isForeign = 1;
            $syncTableModel->isUnique = 1;
            $syncTableModel->isIndex = 1;
            $syncTableModel->isCols = 1;
            $syncTableModel->isRows = 1;
            $syncTableModel->isSuccess = 1;

            //Check Engine type
            if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                if (ArrayHelper::getValue($targetSchema, 'engine')) {
                    if ($sourceSchema->engine->name !== $targetSchema->engine->name) {
                        $syncTableModel->isSuccess = 0;
                        $syncTableModel->isEngine = 0;
                        $errorSummary[] = "<b>Engine</b> (" . $sourceSchema->engine->name . ") doesn't match.";
                    }
                }
            }

            //check table Collation
            if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                if (ArrayHelper::getValue($targetSchema, 'engine')) {
                    if ($sourceSchema->engine->tableCollation !== $targetSchema->engine->tableCollation) {
                        $syncTableModel->isSuccess = 0;
                        $syncTableModel->isEngine = 0;
                        $errorSummary[] = "<b>Engine Collation</b> (" . $sourceSchema->engine->tableCollation . ") doesn't match.";
                    }
                }
            }

            //find Auto Increment
            if (ArrayHelper::getValue($sourceSchema, 'columns')) {
                $hasAutoIncrement = false;
                $isFoundAutoIncrement = false;
                $autoIncrementKey = '';
                foreach (ArrayHelper::getValue($sourceSchema, 'columns') as $sourceColumn) {
                    if ($sourceColumn->autoIncrement) {
                        $hasAutoIncrement = true;
                        $autoIncrementKey = $sourceColumn->name;
                        foreach (ArrayHelper::getValue($targetSchema, 'columns') as $targetColumn) {
                            if ($targetColumn->autoIncrement && $sourceColumn->name === $targetColumn->name) {
                                $isFoundAutoIncrement = true;
                            }
                        }
                    }
                }
                if ($hasAutoIncrement && !$isFoundAutoIncrement) {
                    $syncTableModel->isSuccess = 0;
                    $syncTableModel->autoIncrement = 0;
                    $errorSummary[] = "<b>Auto Increment</b> (" . $autoIncrementKey . ") doesn't set.";
                }
            }


            if ((ArrayHelper::getValue($sourceSchema, 'totalRows') && ArrayHelper::getValue($sourceSchema, 'totalRows')) &&
                (ArrayHelper::getValue($sourceSchema, 'totalRows') !== ArrayHelper::getValue($targetSchema, 'totalRows'))) {
                $syncTableModel->isSuccess = 0;
                $syncTableModel->isRows = 0;
                $gap = (int)(ArrayHelper::getValue($sourceSchema, 'totalRows') - ArrayHelper::getValue($targetSchema, 'totalRows'));
                $errorSummary[] = "<b>Total Records </b>(Gap: ${gap}) doesn't match.";
            }

            //Check if primary key is missing.
            if (ArrayHelper::getValue($sourceSchema, 'primaryKey') && count(ArrayHelper::getValue($targetSchema, 'primaryKey')) > 0) {
                $emptyIndexKeys = [];
                if (ArrayHelper::getValue($targetSchema, 'primaryKey')) {
                    foreach (ArrayHelper::getValue($sourceSchema, 'primaryKey') as $sourcePrimary) {
                        $isMatch = false;
                        foreach (ArrayHelper::getValue($targetSchema, 'primaryKey') as $targetPrimary) {
                            if ($sourcePrimary === $targetPrimary) {
                                $isMatch = true;
                            }
                        }
                        if (!$isMatch) {
                            $emptyIndexKeys[] = $sourcePrimary;
                        }
                    }
                } else {
                    $emptyIndexKeys = ArrayHelper::getValue($sourceSchema, 'primaryKey');
                }

                if ($emptyIndexKeys) {
                    $syncTableModel->isSuccess = 0;
                    $syncTableModel->isPrimary = 0;
                    $errorSummary[] = "<b>Primary Key</b> doesn't set (" . implode(", ", $emptyIndexKeys) . ")";
                }
            }

            //Check if unique column is missing.
            $sourceUniqueColumns = $sourceConnection->schema->findUniqueIndexes($sourceSchema);
            $targetUniqueColumns = $targetConnection->schema->findUniqueIndexes($targetSchema);
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
                    $syncTableModel->isSuccess = 0;
                    $syncTableModel->isUnique = 0;
                    $errorSummary[] = "<b>Unique Key</b> (" . implode(", ", $emptyUniqueKeys) . ") doesn't set.";
                }
            }

            //Check if foreign key is missing. TODO
            if (ArrayHelper::getValue($sourceSchema, 'foreignKeys') && count(ArrayHelper::getValue($sourceSchema, 'foreignKeys')) > 0) {
                $emptyForeignKeys = [];
                foreach (ArrayHelper::getValue($sourceSchema, 'foreignKeys') as $sourceForeignKey) {
                    if (ArrayHelper::getValue($targetSchema, 'foreignKeys') && count(ArrayHelper::getValue($targetSchema, 'foreignKeys')) > 0) {
                        $isMatch = false;
                        foreach (ArrayHelper::getValue($targetSchema, 'foreignKeys') as $targetForeignKey) {
                            if ($sourceForeignKey[0] === $targetForeignKey[0]) {
                                $isMatch = true;
                            }
                        }
                        if (!$isMatch) {
                            $emptyForeignKeys[] = $sourceForeignKey[0];
                        }
                    } else {
                        $emptyForeignKeys[] = $sourceForeignKey[0];
                    }
                }
                if ($emptyForeignKeys) {
                    $syncTableModel->isSuccess = 0;
                    $syncTableModel->isForeign = 0;
                    $errorSummary[] = "<b>Foreign Key</b> (" . implode(", ", $emptyForeignKeys) . ") doesn't set";
                }
            }

            //Check if index key is missing.
            if (ArrayHelper::getValue($sourceSchema, 'index') && count(ArrayHelper::getValue($targetSchema, 'index')) > 0) {
                $emptyIndexKeys = [];
                foreach (ArrayHelper::getValue($sourceSchema, 'index') as $sourceIndex) {
                    if (ArrayHelper::getValue($targetSchema, 'index')) {
                        $isMatch = false;
                        foreach (ArrayHelper::getValue($targetSchema, 'index') as $targetIndex) {
                            if ($sourceIndex['key'] === $targetIndex['key']) {
                                $isMatch = true;
                            }
                        }
                        if (!$isMatch) {
                            $emptyIndexKeys[] = $sourceIndex['key'];
                        }
                    } else {
                        $emptyIndexKeys = $sourceIndex['key'];
                    }
                }


                if (!empty($emptyIndexKeys)) {
                    $syncTableModel->isIndex = 0;
                    $syncTableModel->isSuccess = 0;
                    $errorSummary[] = "<b>Index Key</b> doesn't set (" . implode(", ", $emptyIndexKeys) . ")";
                }
            }

            $findMissingColumnAndIgnoreInCollationCheck = [];

            // compare and find the missing columns with attributes
            if (ArrayHelper::getValue($sourceSchema, 'columns')) {
                foreach (ArrayHelper::getValue($sourceSchema, 'columns') as $sourceColumn) {
                    $columnCompare = [];
                    $columnMatch = false;
                    foreach (ArrayHelper::getValue($targetSchema, 'columns') as $targetColumn) {
                        if ($sourceColumn->name === $targetColumn->name) {
                            $columnMatch = true;
                            $columnCompare = $targetColumn;
                        }
                    }

                    if (!$columnMatch) {
                        $findMissingColumnAndIgnoreInCollationCheck[$sourceColumn->name] = 1;
                        $syncTableModel->isSuccess = 0;
                        $syncTableModel->isCols = 0;
                        $errorSummary[] = "<b>Column</b> (" . $sourceColumn->name . ") doesn't set.";
                    } else {
                        if ($sourceColumn->allowNull !== $columnCompare->allowNull) {
                            $syncTableModel->isSuccess = 0;
                            $syncTableModel->isCols = 0;
                            $nulValue = $sourceColumn->allowNull ? 'allow `true`' : 'allow `false`';
                            $errorSummary[] = "<b>Column</b> (" . $sourceColumn->name . ") Null value must ${nulValue}";
                        }
                        if ($sourceColumn->dbType !== $columnCompare->dbType) {
                            $syncTableModel->isSuccess = 0;
                            $syncTableModel->isCols = 0;
                            $errorSummary[] = "<b>Column</b> (" . $sourceColumn->name . ") DataType must set `" . $sourceColumn->dbType . "`";
                        }
                        if (!empty($sourceColumn->comment) && empty($columnCompare->comment)) {
                            $syncTableModel->isSuccess = 0;
                            $syncTableModel->isCols = 0;
                            $errorSummary[] = "<b>Column</b> (" . $sourceColumn->name . ") Comment should be `" . $sourceColumn->comment . "`";
                        }
                    }
                }
            }

            if (ArrayHelper::getValue($sourceSchema, 'columnCollations')) {
                foreach (ArrayHelper::getValue($sourceSchema, 'columnCollations') as $sourceColumn) {
                    $isMatch = false;
                    foreach (ArrayHelper::getValue($targetSchema, 'columnCollations') as $targetColumn) {
                        if ((trim($sourceColumn['COLUMN_NAME']) === trim($targetColumn['COLUMN_NAME'])) && (trim($sourceColumn['COLLATION']) === trim($targetColumn['COLLATION']))) {
                            $isMatch = true;
                            break;
                        }
                    }

                    if (!$isMatch && !isset($findMissingColumnAndIgnoreInCollationCheck[$sourceColumn['COLUMN_NAME']])) {
                        $syncTableModel->isCols = 0;
                        $syncTableModel->isSuccess = 0;
                        $errorSummary[] = "<b>Collations</b> (" . $sourceColumn['COLUMN_NAME'] . ") doesn't match ('" . $sourceColumn['COLLATION'] . "').";
                    }

                }
            }

            $syncTableModel->errorSummary = Json::encode($errorSummary);
            if (!$syncTableModel->save()) {
                dd($syncTableModel->getErrors());
            } else {
                echo Json::encode($syncTableModel->getErrors());
            }

        } catch
        (Exception $e) {
            echo Json::encode($e->getMessage()) . '\n';
            echo $e->getTraceAsString() . '\n';
        }
    }

    private static function getTotalTimeConsumed($starttime, $endtime)
    {
        $duration = $endtime - $starttime;
        $hours = (int)($duration / 60 / 60);
        $minutes = (int)($duration / 60) - $hours * 60;
        $seconds = (int)$duration - $hours * 60 * 60 - $minutes * 60;
        echo "\n########################################################\n";
        echo "# Total Time consumed Hours: ${hours} Minutes: ${minutes} Seconds: ${seconds}  #\n";
        echo "#######################################################\n";
    }

    public static function createQueue(int $limit, int $beginTime)
    {
        try {
            echo "\n=== Queue Call......\n";
            $syncTableModels = SyncTable::find()->where(['status' => SyncTable::STATUS_TABLE_META_QUEUE])->limit($limit ?: 5)->all();
            if ($syncTableModels) {
                foreach ($syncTableModels as $syncTableModel) {
                    /** @var SyncTable $syncTableModel */
                    $syncTableModel->isEngine = 0;
                    $syncTableModel->autoIncrement = 0;
                    $syncTableModel->isPrimary = 0;
                    $syncTableModel->isForeign = 0;
                    $syncTableModel->isUnique = 0;
                    $syncTableModel->isIndex = 0;
                    $syncTableModel->isCols = 0;
                    $syncTableModel->isRows = 0;
                    $syncTableModel->isSuccess = 0;
                    $syncTableModel->status = SyncTable::STATUS_SCHEMA_COMPLETED;

                    echo "\nTable " . $syncTableModel->tableName . " \n";
                    echo "..";
                    $sourceConnection = DynamicConnection::getConnection($syncTableModel->source->configuration, $syncTableModel->source->dbname);
                    echo "..";
                    $targetConnection = DynamicConnection::getConnection($syncTableModel->target->configuration, $syncTableModel->target->dbname);
                    echo "..";
                    $sourceCoreSchema = $sourceConnection->schema->getTableSchema($syncTableModel->tableName);
                    echo "..";
                    if ($sourceCoreSchema) {
                        echo "..";
                        $targetCoreSchema = $targetConnection->schema->getTableSchema($syncTableModel->tableName);
                        echo "..";
                        //echo  $syncTableModel->source->dbname.'\n';
                        //echo  $syncTableModel->target->dbname.'\n';
                        //echo "\n Step 1 \n";
                        $sourceSchema = self::getTableInfo($sourceCoreSchema, $sourceConnection, $syncTableModel->source->dbname, $syncTableModel->tableName);
                        echo "..";
                        //echo "\n Step 2 \n";
                        $targetSchema = self::getTableInfo($targetCoreSchema, $targetConnection, $syncTableModel->target->dbname, $syncTableModel->tableName);
                        echo "..";
                        //echo "\n Step 3 \n";

                        if ($targetCoreSchema) {
                            echo "..";
                            self::singularTableInfo($syncTableModel, $sourceConnection, $targetConnection, $sourceSchema, $targetSchema);
                            echo "..";
                        } else {
                            $syncTableModel->extra = Json::encode(['source' => $sourceSchema, 'target' => []]);
                            $syncTableModel->errorSummary = Json::encode(["<b>" . $syncTableModel->tableName . "</b> table doesn't exist."]);
                            if (!$syncTableModel->save()) {
                                echo Json::encode($syncTableModel->getErrors());
                            }
                            echo "..";
                        }
                    }
                }
                echo "......\n";
                echo "\n Normal Queue Create with limit 20 \n";
                //self::getTotalTimeConsumed( $beginTime, microtime(true) );
                Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time' => $beginTime]));
            }

            if (!$syncTableModels) {
                echo "\n All table info has been pulled. \n";
                Yii::$app->getCache()->flush();
                self::getTotalTimeConsumed($beginTime, microtime(true));
            }

        } catch (Exception $e) {
            echo "\n Exception: " . Json::encode($e->getMessage() . "\n");
            Yii::error(Json::encode($e->getMessage()), 'queue');
            echo "\n Exception Queue Create with limit 20 \n";
            //Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time'=>$beginTime]));
        }
    }
}