<?php

namespace app\components;

use app\jobs\SchemeInfoJob;
use app\models\SyncTable;
use Exception;
use Yii;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class MySqlSchemaResolver
{

    private static function isModifiedColumn($source, $target, $column, &$sourceSchema, &$targetSchema)
    {
        if(self::isChangeCollationSet($column, $sourceSchema, $targetSchema)){
            return  true;
        }

        if ($source->dbType !== $target->dbType) {
            dd("dbType");
            return true;
        }

        if ($source->allowNull !== $target->allowNull) {
            dd("allowNull");
            return true;
        }

        if (is_object($source->defaultValue)) {
            if(!is_object($target->defaultValue)) {
                dd("defaultValueExpression");
                return true;
            }

            if(($source->defaultValue->expression !== $target->defaultValue->expression)) {
                dd("defaultValueExpression");
                return true;
            }
        }

        if (($source->defaultValue) && ($source->defaultValue !== $target->defaultValue)) {
            dd("defaultValue");
            return true;
        }

        if (!empty($sourceColumn->enumValues) && (explode("", $sourceColumn->enumValues) !== explode(":", $target->enumValues))) {
            dd("enumValues");
            return true;
        }

        if (!empty($source->comment) && ($source->comment !== $target->comment)) {
            dd("comment");
            return true;
        }
    }

    private static function customeSQLTrim($sql)
    {
        return trim(str_replace("CHARACTER_SET", "", str_replace("COMMENT_VALUE", "", str_replace("DEFAULT_VALUE", "", str_replace("NULL_VALUE", "", $sql))))) . ";";
    }

    private static function isChangeCollationSet($column, &$sourceSchema, &$targetSchema)
    {
        $sourceCollations = ArrayHelper::getValue($sourceSchema, 'columnCollations');
        $targetCollations = ArrayHelper::getValue($targetSchema, 'columnCollations');

        if( (isset($sourceCollations[$column]) && isset($targetCollations[$column])) &&
            $sourceCollations[$column]['COLLATION'] === $targetCollations[$column]['COLLATION']
        ) {
            return false;
        }

        return  true;
    }

    private static function getCollationSet($column, &$sourceSchema)
    {
        $sourceCollations = ArrayHelper::getValue($sourceSchema, 'columnCollations');
        if(!empty($sourceCollations[$column]['COLLATION'])){
            $set = substr($sourceCollations[$column]['COLLATION'], 0, strpos($sourceCollations[$column]['COLLATION'], '_'));
            $collate = $sourceCollations[$column]['COLLATION'];
            return "CHARACTER SET ${set} COLLATE ${collate}";
        }
    }

    private static function alterColumn(string &$tableName, &$sourceSchema, &$targetSchema, &$alterQuery)
    {

        echo "<pre>";

        $query = [];
        $afterColumn = null;
        $targetSchemaObject = null;
        $sourceColumns = ArrayHelper::getValue($sourceSchema, 'columns');
        $targetColumns = ArrayHelper::getValue($targetSchema, 'columns');

        foreach ($sourceColumns as $sourceKey => $sourceColumn) {
            $sqlQuery = '';

            $targetSchemaObject = null;
            foreach ($targetColumns as $targetColumn) {
                if ($targetColumn->name === $sourceColumn->name) {
                    $targetSchemaObject = $targetColumn;
                }
            }

            if ($targetSchemaObject) {

                if (self::isModifiedColumn($sourceColumn, $targetSchemaObject, $sourceColumn->name, $sourceSchema, $targetSchema)) {

                    $sqlChangeQuery = "ALTER TABLE `${tableName}` CHANGE `" . $sourceColumn->name . "` `" . $sourceColumn->name . "` DATATYPE CHARACTER_SET NULL_VALUE DEFAULT_VALUE COMMENT_VALUE";

                    $sqlChangeQuery = str_replace("CHARACTER_SET", self::getCollationSet($sourceColumn->name, $sourceSchema), $sqlChangeQuery);

                    if(!self::isChangeCollationSet($sourceColumn->name, $sourceSchema, $targetSchema)) {
                        $sqlChangeQuery = str_replace("CHARACTER_SET", self::getCollationSet($sourceColumn->name, $sourceSchema), $sqlChangeQuery);
                    }

                    if ($sourceColumn->allowNull) {
                        $sqlChangeQuery = str_replace("NULL_VALUE", "NULL", $sqlChangeQuery);
                    } else {
                        $sqlChangeQuery = str_replace("NULL_VALUE", "NOT NULL", $sqlChangeQuery);
                    }

                    if (is_object($sourceColumn->defaultValue)) {
                        if ($sourceColumn->defaultValue->expression) {
                            $sqlChangeQuery = str_replace("DEFAULT_VALUE", "DEFAULT " . $sourceColumn->defaultValue->expression, $sqlChangeQuery);
                        }
                    } else {
                        if ($sourceColumn->defaultValue !== $targetSchemaObject->defaultValue) {
                            $sqlChangeQuery = str_replace("DEFAULT_VALUE", "DEFAULT '" . $sourceColumn->defaultValue . "'", $sqlChangeQuery);
                        }
                    }

                    if (!empty($sourceColumn->comment) && ($sourceColumn->comment !== $targetSchemaObject->comment)) {
                        $sqlChangeQuery = str_replace("COMMENT_VALUE", "COMMENT '" . $sourceColumn->comment . "'", $sqlChangeQuery);
                    }

                    if (!empty($sourceColumn->enumValues)) {
                        $values = substr($sourceColumn->dbType, 4, strlen($sourceColumn->dbType));
                        $sqlChangeQuery = str_replace("DATATYPE", "ENUM${values}", $sqlChangeQuery);
                    } else {
                        $sqlChangeQuery = str_replace("DATATYPE", strtoupper($sourceColumn->dbType), $sqlChangeQuery);
                    }

                    $sqlQuery = self::customeSQLTrim($sqlChangeQuery);

                }

            } else {

                $sqlQuery = "ALTER TABLE `${tableName}` ADD `" . $sourceColumn->name . "` DATATYPE CHARACTER_SET NULL_VALUE DEFAULT_VALUE COMMENT_VALUE AFTER_VALUE";
                $sqlQuery = str_replace("CHARACTER_SET", self::getCollationSet($sourceColumn->name, $sourceSchema), $sqlQuery);

                if (!empty($sourceColumn->enumValues)) {
                    $values = substr($sourceColumn->dbType, 4, strlen($sourceColumn->dbType));
                    $sqlQuery = str_replace("DATATYPE", "ENUM${values}", $sqlQuery);
                } else {
                    $sqlQuery = str_replace("DATATYPE", strtoupper($sourceColumn->dbType), $sqlQuery);
                }

                if ($sourceColumn->allowNull) {
                    $sqlQuery = str_replace("NULL_VALUE", "NULL", $sqlQuery);
                } else {
                    $sqlQuery = str_replace("NULL_VALUE", "NOT NULL", $sqlQuery);
                }

                if (is_object($sourceColumn->defaultValue)) {
                    if (empty($sourceColumn->defaultValue->expression)) {
                        $sqlQuery = str_replace("DEFAULT_VALUE", "DEFAULT " . $sourceColumn->defaultValue->expression, $sqlQuery);
                    }
                } else {
                    if(!empty($sourceColumn->defaultValue)){
                        $sqlQuery = str_replace("DEFAULT_VALUE", "DEFAULT '" . $sourceColumn->defaultValue . "'", $sqlQuery);
                    }
                }

                if (!empty($sourceColumn->comment)) {
                    $sqlQuery = str_replace("COMMENT_VALUE", "COMMENT '" . $sourceColumn->comment . "'", $sqlQuery);
                }

                if ($afterColumn) {
                    $sqlQuery = str_replace("AFTER_VALUE", "AFTER " . $afterColumn->name, $sqlQuery);
                }

                $sqlQuery = self::customeSQLTrim($sqlQuery);
            }
            //dd($sqlQuery);
            if (!empty($sqlQuery)) {
                $alterQuery[] = $sqlQuery;
            }

            $afterColumn = $sourceColumn;
        }
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
                            $characterSet = substr($sourceSchema->engine->tableCollation, 0, strpos($sourceSchema->engine->tableCollation, '_'));
                            $alterQuery[] = "ALTER TABLE <table-name> CHARACTER SET `${characterSet}` COLLATE `" . $sourceSchema->engine->tableCollation . "`;";
                        }
                    }
                }

                // Find Missing Column Alter
                if (ArrayHelper::getValue($sourceSchema, 'columns')) {
                    self::alterColumn($tableName, $sourceSchema, $targetSchema, $alterQuery);
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
                if (ArrayHelper::getValue($sourceSchema, 'index') && count(ArrayHelper::getValue($sourceSchema, 'index')) > 0) {
                    foreach (ArrayHelper::getValue($sourceSchema, 'index') as $sourceIndex) {
                        if (ArrayHelper::getValue($targetSchema, 'index') && count(ArrayHelper::getValue($targetSchema, 'index')) > 0) {
                            $isMatch = false;
                            foreach (ArrayHelper::getValue($targetSchema, 'index') as $targetIndex) {
                                if ($sourceIndex['key'] === $targetIndex['key']) {
                                    $isMatch = true;
                                }
                            }
                            if (!$isMatch) {
                                if (!in_array($sourceIndex['key'], $commonIndex)) {
                                    $alterQuery[] = "ALTER TABLE `${tableName}` ADD INDEX `" . $sourceIndex['index'] . "` (`" . $sourceIndex['key'] . "`);";
                                }
                            }
                        } else {
                            if (!in_array($sourceIndex['key'], $commonIndex)) {
                                $alterQuery[] = "ALTER TABLE `${tableName}` ADD INDEX `" . $sourceIndex['index'] . "` (`" . $sourceIndex['key'] . "`);";
                            }
                        }
                    }
                }

                $alterQuery[] = "SET FOREIGN_KEY_CHECKS=1;";


                //dd($alterQuery, count($alterQuery));die();

                dd(implode(" \n", $alterQuery));
                die();

                if ($alterQuery && count($alterQuery) > 2) {
                    $targetConnection->createCommand(implode(" \n", $alterQuery))->execute();
                    if ($model->save()) {
                        echo "${tableName} has been modified \n";
                    } else {
                        echo Json::encode($model->getErrors());
                    }
                }

                Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time' => microtime(true)]));
                echo "Queue Called";
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