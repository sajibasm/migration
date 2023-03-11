<?php

namespace app\components;


use app\jobs\SchemeInfoJob;
use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use Exception;
use Yii;
use yii\db\Connection;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;


class SchemaInfo
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

    protected function getEngine(Connection &$connection, string $database, $table, $clearCache = false)
    {
        if ($clearCache) {
            return Yii::$app->getCache()->flush();
        }

        $key = md5(trim($table) . "_engine_info" . $database);
        $infoSchemaData = Yii::$app->getCache()->get($key);
        if ($infoSchemaData === false) {
            //echo "\nEngine Data from SQL\n";
            $schemaData = $connection->createCommand("SELECT * FROM  information_schema.TABLES WHERE  TABLE_SCHEMA = '${database}';")->queryAll();
            foreach ($schemaData as $row) {
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

    protected function getIndex(Connection &$connection, string $database, $table, $clearCache = false)
    {
        if ($clearCache) {
            return Yii::$app->getCache()->flush();
        }

        $key = md5(trim($table) . "_index_info_cache" . $database);
        $infoSchemaData = Yii::$app->getCache()->get($key) ?: [];

        if (empty($infoSchemaData)) {
            //echo "\nIndex Data from SQL\n";
            $schemaData = $connection->createCommand("SELECT DISTINCT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '${database}';")->queryAll();
            foreach ($schemaData as $row) {
                if ($row['TABLE_NAME'] === $table && $row['INDEX_NAME'] !== 'PRIMARY') {
                    $infoSchemaData[] = ['index' => $row['INDEX_NAME'], 'key' => $row['COLUMN_NAME']];
                }
            }
            Yii::$app->getCache()->set(md5(trim($row['TABLE_NAME']) . "_index_info_cache" . $database), $infoSchemaData, 180);
        }
        return $infoSchemaData;
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
        $schema->setEngine(self::getEngine($connection, $database, $table, false));
        echo "..";
        $schema->setIndex(self::getIndex($connection, $database, $table, false));
        echo "..";
        return $schema;
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

    public static function singularTableInfo(SyncTable &$syncModel, Connection $sourceConnection, Connection $targetConnection, SchemaObject $sourceSchema, SchemaObject $targetSchema)
    {
        try {
            $errorSummary = [];
            $syncModel->tableName = $sourceSchema->fullName;
            $syncModel->isEngine = 1;
            $syncModel->autoIncrement = 1;
            $syncModel->isPrimary = 1;
            $syncModel->isForeign = 1;
            $syncModel->isUnique = 1;
            $syncModel->isIndex = 1;
            $syncModel->isCols = 1;
            $syncModel->isRows = 1;
            $syncModel->isSuccess = 1;
            $syncModel->extra = Json::encode(['source' => $sourceSchema, 'target' => $targetSchema]);
            $syncModel->status = SyncTable::STATUS_SCHEMA_COMPLETED;

            //Check Engine type
            if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                if (ArrayHelper::getValue($targetSchema, 'engine')) {
                    if ($sourceSchema->engine->name !== $targetSchema->engine->name) {
                        $errorSummary[] = "<b>Engine</b> (" . $sourceSchema->engine->name . ") doesn't match ";
                        $syncModel->isEngine = 0;
                        $syncModel->isSuccess = 0;
                    }
                }
            }

            //check table Collation
            if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                if (ArrayHelper::getValue($targetSchema, 'engine')) {
                    if ($sourceSchema->engine->tableCollation !== $targetSchema->engine->tableCollation) {
                        $errorSummary[] = "<b>Engine Collation</b> (" . $sourceSchema->engine->tableCollation . ") doesn't match ";
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
                    } else {
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
            if (ArrayHelper::getValue($sourceSchema, 'primaryKey') && count(ArrayHelper::getValue($targetSchema, 'primaryKey')) > 0) {
                $emptyPrimaryKeys = [];
                if (ArrayHelper::getValue($targetSchema, 'primaryKey')) {
                    foreach (ArrayHelper::getValue($sourceSchema, 'primaryKey') as $sourcePrimary) {
                        $isMatch = false;
                        foreach (ArrayHelper::getValue($targetSchema, 'primaryKey') as $targetPrimary) {
                            if ($sourcePrimary === $targetPrimary) {
                                $isMatch = true;
                            }
                        }
                        if (!$isMatch) {
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
                    $errorSummary[] = "<b>Unique Key</b> (" . implode(", ", $emptyUniqueKeys) . ") doesn't set.";
                    $syncModel->isUnique = 0;
                    $syncModel->isSuccess = 0;
                }
            }

            //Check if foreign key is missing.
            if (ArrayHelper::getValue($sourceSchema, 'foreignKeys') && count(ArrayHelper::getValue($targetSchema, 'foreignKeys')) > 0) {
                $emptyForeignKeys = [];

                foreach (ArrayHelper::getValue($sourceSchema, 'foreignKeys') as $sourceForeignKeyName => $sourceForeignKey) {
                    if (ArrayHelper::getValue($targetSchema, 'foreignKeys')) {
                        $isMatch = false;
                        foreach (ArrayHelper::getValue($targetSchema, 'foreignKeys') as $targetForeignKeyName => $targetForeignKey) {
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
                    $errorSummary[] = "<b>Foreign Key</b> (" . implode(", ", $emptyForeignKeys) . ") doesn't set";
                    $syncModel->isForeign = 0;
                    $syncModel->isSuccess = 0;
                }
            }

            //Check if index key is missing.
            if (ArrayHelper::getValue($sourceSchema, 'index') && count(ArrayHelper::getValue($targetSchema, 'index')) > 0) {
                $emptyPrimaryKeys = [];
                if (ArrayHelper::getValue($targetSchema, 'index')) {
                    foreach (ArrayHelper::getValue($sourceSchema, 'index') as $sourceIndex) {
                        $isMatch = false;
                        foreach (ArrayHelper::getValue($targetSchema, 'index') as $targetIndex) {
                            if ($sourceIndex === $targetIndex) {
                                $isMatch = true;
                            }
                        }
                        if (!$isMatch) {
                            $emptyPrimaryKeys[] = $sourceIndex;
                        }
                    }
                } else {
                    $emptyPrimaryKeys = ArrayHelper::getValue($sourceSchema, 'index');
                }

                if ($emptyPrimaryKeys) {
                    $errorSummary[] = "<b>Index Key</b> doesn't set (" . implode(", ", $emptyPrimaryKeys) . ")";
                    $syncModel->isIndex = 0;
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
                            if (is_array($sourceColumn) && !is_array($targetColumn) && (count($sourceColumn) > 0 && count($targetColumn) > 0)) {
                                try {
                                    $columnCompare = array_diff($sourceColumn, $targetColumn);
                                } catch (\Exception $e) {
                                    echo $e->getMessage() . 'Got an error';
                                }
                            }
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

            $syncModel->errorSummary = Json::encode($errorSummary);

            if (!$syncModel->save()) {
                dd($syncModel->getErrors());
            } else {
                echo Json::encode($syncModel->getErrors());
            }

        } catch (Exception $e) {
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


    public static function schemaQueue(int $limit, int $beginTime)
    {
        try {
            echo "\n=== Queue Call......\n";
            $syncTableModels = SyncTable::find()->where(['status' => SyncTable::STATUS_TABLE_META_QUEUE])->limit($limit ?: 5)->all();
            if ($syncTableModels) {
                foreach ($syncTableModels as $syncTableModel) {
                    /** @var SyncTable $syncTableModel */
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
                            $syncTableModel->isSuccess = 0;
                            $syncTableModel->extra = Json::encode(['source' => $sourceSchema, 'target' => []]);
                            $syncTableModel->status = SyncTable::STATUS_SCHEMA_COMPLETED;
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