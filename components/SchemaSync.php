<?php

namespace app\components;

use app\jobs\SchemeInfoJob;
use app\models\SyncTable;
use Exception;
use Yii;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class SchemaSync
{

    private static function alterColumn($tableName, $schema, $after)
    {

        $sql = "ALTER TABLE TABLE_NAME ADD COLUMN `COLUMN_NAME` DATATYPE NULL_VALUE DEFAULT_VALUE AFTER `AFTER_COLUMN`;";
        $sql = str_replace("TABLE_NAME", $tableName, $sql);
        $sql = str_replace("COLUMN_NAME", $schema->name, $sql);
        $sql = str_replace("AFTER_COLUMN", $after->name, $sql);
        $sql = str_replace("DATATYPE", strtoupper($schema->dbType), $sql);

        if ($schema->allowNull) {
            $sql = str_replace("NULL_VALUE", "NOT NULL", $sql);
        } else {
            $sql = str_replace("NULL_VALUE", "", $sql);
        }

        if ($schema->defaultValue) {
            $sql = str_replace("DEFAULT_VALUE", "DEFAULT `" . $schema->defaultValue . "`", $sql);
        } else {
            $sql = str_replace("DEFAULT_VALUE", "", $sql);
        }

        return $sql;
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

                $alterQuery = [];
                $alterQuery[] = "SET FOREIGN_KEY_CHECKS=0;";

                //Check Engine type
                if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                    if (ArrayHelper::getValue($targetSchema, 'engine')) {
                        if ($sourceSchema->engine->name !== $targetSchema->engine->name) {
                            $alterQuery[] = "ALTER TABLE `${tableName}` ENGINE = " . $sourceSchema->engine->name . ";";
                        }
                    }
                }

                //check table Collation
                if (ArrayHelper::getValue($sourceSchema, 'engine')) {
                    if (ArrayHelper::getValue($targetSchema, 'engine')) {
                        if ($sourceSchema->engine->tableCollation !== $targetSchema->tableCollation) {
                            $alterQuery[] = "ALTER TABLE `${tableName}` COLLATE utf8mb4_0900_ai_ci;";
                        }
                    }
                }

                //find Auto Increment
                if (ArrayHelper::getValue($sourceSchema, 'columns')) {

                    $autoIncrementKey = '';
                    $autoIncrementId = false;

                    foreach (ArrayHelper::getValue($sourceSchema, 'columns') as $sourceColumn) {
                        if ($sourceColumn->autoIncrement) {
                            $autoIncrementKey = $sourceColumn->name;
                            foreach (ArrayHelper::getValue($targetSchema, 'columns') as $targetColumn) {
                                if ($targetColumn->autoIncrement) {
                                    if ($sourceColumn->name !== $targetColumn->name) {
                                        $autoIncrementId = true;
                                        $alterQuery[] = "ALTER TABLE `${tableName}` CHANGE  `" . $targetColumn->name . "` `" . $targetColumn->name . "` INT NOT NULL;";
                                        $alterQuery[] = "ALTER TABLE `${tableName}` CHANGE `" . $sourceColumn->name . "` `" . $sourceColumn->name . "` INT NOT NULL AUTO_INCREMENT; ";
                                    }
                                }
                            }
                        }
                    }

                    if (!$autoIncrementId) {
                        $alterQuery[] = "ALTER TABLE `${tableName}` CHANGE `${autoIncrementKey}` `${autoIncrementKey}` INT NOT NULL AUTO_INCREMENT; ";
                    }
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
                        $alterQuery[] = "ALTER TABLE `${tableName}` ADD PRIMARY KEY(" . implode(",", $emptyIndexKeys) . ");";
                    }
                }


                //Check if unique column is missing.
                $sourceUniqueColumns = $sourceConnection->schema->findUniqueIndexes($sourceSchema);
                $targetUniqueColumns = $targetConnection->schema->findUniqueIndexes($targetSchema);

                if ($sourceUniqueColumns) {
                    $emptyUniqueKeys = [];
                    foreach ($sourceUniqueColumns as $constraint => $sourceUniqueColumn) {
                        if ($targetUniqueColumns) {
                            $match = false;
                            foreach ($targetUniqueColumns as $targetUniqueColumn) {
                                if ($targetUniqueColumn[0] === $sourceUniqueColumn[0]) {
                                    $match = true;
                                }
                            }
                            if (!$match) {
                                $alterQuery[] = "ALTER TABLE `${tableName}` ADD CONSTRAINT `${constraint}` UNIQUE(" . $sourceUniqueColumn[0] . ");";
                            }
                        } else {
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
                                $emptyForeignKeys[] = $sourceForeignKey[0];
                            }
                        } else {
                            $emptyForeignKeys[] = $sourceForeignKey[0];
                        }
                    }

                    if ($emptyForeignKeys) {
                        $errorSummary[] = "<b>Foreign Key</b> (" . implode(", ", $emptyForeignKeys) . ") doesn't set";
                        $model->isForeign = 0;
                        $model->isSuccess = 0;
                    }
                }

                // compare and find the missing columns with attributes
                if (ArrayHelper::getValue($sourceSchema, 'columns')) {

                    $afterColumn = [];


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
                            self::alterColumn($tableName, $sourceColumn, $afterColumn);
                            die("not found column");
                        }

                        if ($columnCompare) {

                            //To Check Column Comments missing from source to target
                            if (ArrayHelper::getValue($columnCompare, 'comment')) {
                                $errorSummary[] = "<b>" . $sourceColumn->name . "</b> (" . ArrayHelper::getValue($columnCompare, 'comment') . ") comment doesn't set.";
                                //ALTER TABLE `category` CHANGE `name` `name` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'Test';
                            }

                            //To Check Column dataType.
                            if (ArrayHelper::getValue($columnCompare, 'dbType')) {

                            }
                        }

                        $afterColumn = $sourceColumn;

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
                                    $alterQuery[] = "ALTER TABLE `${tableName}` ADD INDEX `" . $sourceIndex['index'] . "` (`" . $sourceIndex['key'] . "`);";
                                }
                            } else {
                                $alterQuery[] = "ALTER TABLE `${tableName}` ADD INDEX `" . $sourceIndex['index'] . "` (`" . $sourceIndex['key'] . "`);";
                            }
                        }
                    }
                }


                $alterQuery[] = "SET FOREIGN_KEY_CHECKS=1;";

                if ($alterQuery) {

                    dd(implode(" ", $alterQuery));
                    die();

                    $targetConnection->createCommand($createSql)->execute();
                    if ($model->save()) {
                        echo "${tableName} has been created \n";
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


    public static function schema(int &$id)
    {
        try {
            echo "\n=== Queue Call......\n";
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
                $sourceSchema = SchemaInfo::getTableInfo($sourceCoreSchema, $sourceConnection, $syncTableModel->source->dbname, $syncTableModel->tableName);
                echo "..";
                $targetSchema = SchemaInfo::getTableInfo($targetCoreSchema, $targetConnection, $syncTableModel->target->dbname, $syncTableModel->tableName);
                echo "..";
                self::generateSchema($syncTableModel, $sourceConnection, $targetConnection, $sourceSchema, $targetSchema);
                echo "..";
            }


            echo "......\n";
            echo "\n Normal Queue Create with limit 20 \n";
            //self::getTotalTimeConsumed( $beginTime, microtime(true) );
            //Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time' => $beginTime]));


            if (!$syncTableModel) {
                echo "\n All table info has been pulled. \n";
                //self::getTotalTimeConsumed($beginTime, microtime(true));
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