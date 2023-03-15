<?php

namespace app\components;

use app\jobs\SchemeInfoJob;
use app\models\SyncTable;
use Exception;
use Yii;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class MySqlSchemaResolver
{


    private static function alterColumn(string &$tableName, &$sourceSchema, &$targetSchema)
    {


        $sql = '';
        $afterColumn = [];
        $targetSchemaObject = null;
        $sourceColumns = ArrayHelper::getValue($sourceSchema, 'columns');
        $targetColumns = ArrayHelper::getValue($targetSchema, 'columns');


        $sourceCollations = ArrayHelper::getValue($sourceSchema, 'columnCollations');
        $targetCollations = ArrayHelper::getValue($targetSchema, 'columnCollations');


        foreach ($sourceCollations as $sourceKey => $sourceCollation) {

            $targetSchemaObject = null;

            foreach ($targetCollations as $targetCollation) {
                if ($sourceCollation['COLUMN_NAME'] === $targetCollation['COLUMN_NAME']) {
                    $targetSchemaObject = $targetCollation;
                    break;
                }
            }

            if ($targetSchemaObject) {

                $sql = "ALTER TABLE `${tableName}` CHANGE `".$sourceCollation->name."` `".$sourceCollation->name."` ";

                if (str_contains($sourceCollation->dbType, 'enum')) {
                    $dataType = substr($sourceCollation->dbType, 0, 4);
                    $default = substr($sourceCollation->dbType, 4, strlen($sourceCollation->dbType));
                    $sql = str_replace("DATATYPE", strtoupper($dataType) . $default, $sql);
                } else {
                    $sql .= strtoupper($sourceCollation->dbType) . " ";
                }

                if ($sourceCollation->defaultValue) {
                    if (in_array($sourceCollation->dbType, ['date', 'time', 'year', 'timestamp', 'datetime'])) {
                        $sql .= " NOT NULL DEFAULT " . $sourceCollation->defaultValue;
                    } else {
                        $sql .= " NOT NULL DEFAULT '" . $sourceCollation->defaultValue . "'";
                    }

                } elseif ($sourceCollation->allowNull) {
                    $sql .= " NULL";
                } else {
                    $sql .= " NOT NULL";
                }


                if (!empty($sourceCollation->comment) && ($sourceCollation->comment !== $targetSchemaObject->comment)) {
                    $sql .= " COMMENT '" . $sourceCollation->comment . "'";
                }

            } else {

                $sql = "ALTER TABLE `${tableName}` ADD `".$sourceCollation->name."` DATATYPE";
                if (str_contains($sourceCollation->dbType, 'enum')) {
                    $dataType = substr($sourceCollation->dbType, 0, 4);
                    $default = substr($sourceCollation->dbType, 4, strlen($sourceCollation->dbType));
                    $sql = str_replace("DATATYPE", strtoupper($dataType) . $default, $sql);
                } else {
                    $sql = str_replace("DATATYPE", strtoupper($sourceCollation->dbType), $sql);
                }

                if ($sourceCollation->defaultValue) {
                    if (in_array($sourceCollation->dbType, ['date', 'time', 'year', 'timestamp', 'datetime'])) {
                        $sql .= " NOT NULL DEFAULT " . $sourceCollation->defaultValue;
                    } else {
                        $sql .= " NOT NULL DEFAULT '" . $sourceCollation->defaultValue . "'";
                    }

                } elseif ($sourceCollation->allowNull) {
                    $sql .= " ";
                } else {
                    $sql .= " NOT NULL";
                }

                if ($sourceCollation->comment !== $targetSchemaObject->comment) {
                    $sql .= " COMMENT '" . $sourceCollation->comment . "'";
                }

                if ($afterColumn) {
                    $sql .= " AFTER  '" . $sourceCollation->name . "'";
                }
            }

            $afterColumn = $sourceCollation;
        }

        return $sql . ";";

    }


    private static function generateSchema(SyncTable $model, Connection $sourceConnection, Connection $targetConnection, &$sourceSchema, &$targetSchema)
    {
        try {
            $errorSummary = [];

            $extra = Json::decode($model->extra);
            $tableName = $model->tableName;
            $model->status = SyncTable::STATUS_TABLE_META_QUEUE;

            if ($targetSchema && empty($targetSchema->name)) {
                $tableInfo = $sourceConnection->createCommand("show create table ${tableName};")->queryAll();
                $createSql = $tableInfo[0]['Create Table'];
                $targetConnection->createCommand($createSql)->execute();
                if ($model->save()) {
                    echo "${tableName} has been created \n";
                    Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time' => microtime(true)]));
                }
            } else {

                $commonIndex = [];
                $alterQuery[] = "SET FOREIGN_KEY_CHECKS=0;";

                //Check Engine type
                if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                    if (ArrayHelper::getValue($targetSchema, 'engine')) {
                        if ($sourceSchema->engine->name !== $targetSchema->engine->name) {
                            $alterQuery[] = "ALTER TABLE `${tableName}` ENGINE = `" . $sourceSchema->engine->name . "`;";
                        }
                    }
                }

                //check table Collation
                if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                    if (ArrayHelper::getValue($targetSchema, 'engine')) {
                        if ($sourceSchema->engine->tableCollation !== $targetSchema->engine->tableCollation) {
                            $alterQuery[] = "ALTER TABLE `${tableName}` COLLATE `".$sourceSchema->engine->tableCollation."`;";
                        }
                    }
                }

                // Find Missing Column Alter
                if (ArrayHelper::getValue($sourceSchema, 'columns')) {
                    $alterQuery[] = self::alterColumn($tableName, $sourceSchema, $targetSchema );
                }


                dd($alterQuery);
                die();

                //Check if primary key is missing.
                if (ArrayHelper::getValue($sourceSchema, 'primaryKey') && count(ArrayHelper::getValue($targetSchema, 'primaryKey')) > 0) {
                    $emptyIndexKeys = [];
                    if (ArrayHelper::getValue($targetSchema, 'primaryKey')) {
                        foreach (ArrayHelper::getValue($sourceSchema, 'primaryKey') as $sourcePrimary) {

                            $isMatch = false;
                            foreach (ArrayHelper::getValue($targetSchema, 'primaryKey') as $targetPrimary) {
                                if ($sourcePrimary === $targetPrimary) {
                                    $isMatch = true;
                                    break;
                                }
                            }

                            if (!$isMatch) {
                                $emptyIndexKeys[] = $sourcePrimary;
                            }
                        }
                    }
                    if ($emptyIndexKeys) {
                        $commonIndex = $emptyIndexKeys;
                        $alterQuery[] = "ALTER TABLE `${tableName}` ADD PRIMARY KEY(" . implode(",", $emptyIndexKeys) . ");";
                    }
                }

                //find Auto Increment
                if (ArrayHelper::getValue($sourceSchema, 'columns')) {
                    $hasAutoIncrement = false;
                    $autoIncrementKey = '';
                    $isFoundAutoIncrementId = false;
                    foreach (ArrayHelper::getValue($sourceSchema, 'columns') as $sourceColumn) {
                        if ($sourceColumn->autoIncrement) {
                            $hasAutoIncrement = true;
                            $autoIncrementKey = $sourceColumn->name;
                            foreach (ArrayHelper::getValue($targetSchema, 'columns') as $targetColumn) {
                                if ($targetColumn->autoIncrement) {
                                    if ($sourceColumn->name === $targetColumn->name) {
                                        $isFoundAutoIncrementId = true;
                                    }
                                }
                            }
                            break;
                        }
                    }

                    if ($hasAutoIncrement && !$isFoundAutoIncrementId) {
                        $alterQuery[] = "ALTER TABLE `${tableName}` CHANGE `${autoIncrementKey}` `${autoIncrementKey}` INT NOT NULL AUTO_INCREMENT;";
                    }
                }


                //Check if unique column is missing.
                $sourceUniqueColumns = $sourceConnection->schema->findUniqueIndexes($sourceSchema);
                $targetUniqueColumns = $targetConnection->schema->findUniqueIndexes($targetSchema);

                if ($sourceUniqueColumns) {
                    foreach ($sourceUniqueColumns as $constraint => $sourceUniqueColumn) {
                        if ($targetUniqueColumns) {
                            $match = false;
                            foreach ($targetUniqueColumns as $targetUniqueColumn) {
                                if ($targetUniqueColumn[0] === $sourceUniqueColumn[0]) {
                                    $match = true;
                                }
                            }
                            if (!$match) {
                                $commonIndex[] = $sourceUniqueColumn[0];
                                $alterQuery[] = "ALTER TABLE `${tableName}` ADD CONSTRAINT `${constraint}` UNIQUE(" . $sourceUniqueColumn[0] . ");";
                            }
                        } else {
                            $commonIndex[] = $sourceUniqueColumn[0];
                            $alterQuery[] = "ALTER TABLE `${tableName}` ADD CONSTRAINT `${constraint}` UNIQUE(" . $sourceUniqueColumn[0] . ");";
                        }
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
                                $commonIndex[] = $sourceForeignKey[0];
                                $emptyForeignKeys[] = $sourceForeignKey[0];
                            }
                        } else {
                            $commonIndex[] = $sourceForeignKey[0];
                            $emptyForeignKeys[] = $sourceForeignKey[0];
                        }
                    }

                    if (!empty($emptyForeignKeys)) {
                        //$errorSummary[] = "<b>Foreign Key</b> (" . implode(", ", $emptyForeignKeys) . ") doesn't set";
                        //TODO
                    }
                }


                //Check if index key is missing.
                if (ArrayHelper::getValue($sourceSchema, 'index') && count(ArrayHelper::getValue($targetSchema, 'index')) > 0) {
                    $emptyIndexKeys = [];
                    if (ArrayHelper::getValue($targetSchema, 'index')) {
                        foreach (ArrayHelper::getValue($sourceSchema, 'index') as $sourceIndex) {
                            if (ArrayHelper::getValue($targetSchema, 'index')) {
                                $isMatch = false;
                                foreach (ArrayHelper::getValue($targetSchema, 'index') as $targetIndex) {
                                    if ($sourceIndex['key'] === $targetIndex['key']) {
                                        $isMatch = true;
                                    }
                                }
                                if (!$isMatch) {

                                    if (!in_array($sourceIndex['index'], $commonIndex)) {
                                        $alterQuery[] = "ALTER TABLE `${tableName}` ADD INDEX `" . $sourceIndex['index'] . "` (`" . $sourceIndex['key'] . "`);";
                                    }
                                }
                            } else {
                                if (!in_array($sourceIndex['index'], $commonIndex)) {
                                    $alterQuery[] = "ALTER TABLE `${tableName}` ADD INDEX `" . $sourceIndex['index'] . "` (`" . $sourceIndex['key'] . "`);";
                                }
                            }
                        }
                    }
                }

                $alterQuery[] = "SET FOREIGN_KEY_CHECKS=1;";
                dd(implode(" \n", $alterQuery));die();

                if ($alterQuery) {
                    $targetConnection->createCommand(implode(" \n", $alterQuery))->execute();
                    if ($model->save()) {
                        echo "${tableName} has been modified \n";
                        Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time' => microtime(true)]));
                    }
                }

                if (!$model->save()) {
                    dd($model->getErrors());
                } else {
                    echo Json::encode($model->getErrors());
                }
            }

        } catch (Exception $e) {
            echo Json::encode($e->getMessage()) . '\n';
            echo $e->getTraceAsString() . '\n';
        }
    }


    public static function createQueue(int $id, int $beginTime)
    {
        try {
            echo "\n=== Queue Call......\n";
            Yii::$app->getCache()->flush();
            /** @var SyncTable $syncTableModel */
            $syncTableModel = SyncTable::findOne($id);
            if ($syncTableModel) {
                echo "\nTable " . $syncTableModel->tableName . " \n";
                echo "..";
                $sourceConnection = DynamicConnection::getConnection($syncTableModel->source->configuration, $syncTableModel->source->dbname);
                echo "..";
                $targetConnection = DynamicConnection::getConnection($syncTableModel->target->configuration, $syncTableModel->target->dbname);
                echo "..";
                $sourceCoreSchema = $sourceConnection->schema->getTableSchema($syncTableModel->tableName);
                echo "..";
                $targetCoreSchema = $targetConnection->schema->getTableSchema($syncTableModel->tableName);
                echo "..";
                $sourceSchema = MysqlSchemaConflict::getTableInfo($sourceCoreSchema, $sourceConnection, $syncTableModel->source->dbname, $syncTableModel->tableName);
                echo "..";
                $targetSchema = MysqlSchemaConflict::getTableInfo($targetCoreSchema, $targetConnection, $syncTableModel->target->dbname, $syncTableModel->tableName);
                echo "..";
                self::generateSchema($syncTableModel, $sourceConnection, $targetConnection, $sourceSchema, $targetSchema);
                echo "..";
            }

            echo "........\n";

            if (!$syncTableModel) {
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

}