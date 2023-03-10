<?php

namespace app\components;
use app\models\SyncTable;
use Yii;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class SchemaSync
{

    public static function generateSchema(SyncTable &$syncModel, Connection $sourceConnection, Connection $targetConnection, Schema $sourceSchema, Schema $targetSchema)
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
            $syncModel->extra = Json::encode($sourceSchema);
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
}