<?php

namespace app\components;

class SyncObject
{
    public $sourceHost;
    public $destinationHost;
    public $table;
    public $engine;
    public $engineType = '';

    public $primary;
    public $primaryKeys;
    public $foreign;
    public $foreignKeys;

    public $autoIncrement;
    public $autoIncrementKeys;

    public $unique;
    public $uniqueKeys;

    public $index;
    public $indexKeys;

    public $col;
    public $numberOfCols;

    public $rows;
    public $numberOfRows;
    public $extra;

    public $error;
    public $errorSummary;

    public function __construct()
    {
        $this->engine = false;
        $this->primary = false;
        $this->primaryKeys = [];
        $this->foreign = false;
        $this->foreignKeys = [];
        $this->autoIncrement = false;
        $this->unique = false;
        $this->uniqueKeys = [];
        $this->index = false;
        $this->indexKeys = [];
        $this->col = false;
        $this->rows = false;
        $this->error = false;
    }

    /**
     * @return string
     */
    public function getSourceHost()
    {
        return $this->sourceHost;
    }

    /**
     * @param mixed $sourceHost
     */
    public function setSourceHost($sourceHost): void
    {
        $this->sourceHost = $sourceHost;
    }

    /**
     * @return string
     */
    public function getDestinationHost(): string
    {
        return $this->destinationHost;
    }

    /**
     * @param mixed $destinationHost
     */
    public function setDestinationHost($destinationHost): void
    {
        $this->destinationHost = $destinationHost;
    }

    /**
     * @return mixed
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table): void
    {
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * @param mixed $engine
     */
    public function setEngine($engine): void
    {
        $this->engine = $engine;
    }

    /**
     * @return mixed
     */
    public function getEngineType()
    {
        return $this->engineType;
    }

    /**
     * @param mixed $engineType
     */
    public function setEngineType($engineType): void
    {
        $this->engineType = $engineType;
    }

    /**
     * @return mixed
     */
    public function getPrimary()
    {
        return $this->primary;
    }

    /**
     * @param mixed $primary
     */
    public function setPrimary($primary): void
    {
        $this->primary = $primary;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * @param mixed $primaryKeys
     */
    public function setPrimaryKeys($primaryKeys): void
    {
        if (is_array($primaryKeys)) {
            if(empty($this->primaryKeys)){
                $this->primaryKeys = $primaryKeys;
            }else{
                $this->primaryKeys = array_merge($this->primaryKeys, $primaryKeys);
            }
        } else {
            $this->primaryKeys[] = $primaryKeys;
        }


    }

    /**
     * @return mixed
     */
    public function getForeign()
    {
        return $this->foreign;
    }

    /**
     * @param mixed $foreign
     */
    public function setForeign($foreign): void
    {
        $this->foreign = $foreign;
    }

    /**
     * @return mixed
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * @param mixed $foreignKeys
     */
    public function setForeignKeys($foreignKeys): void
    {
        try {
            if (is_array($foreignKeys)) {
                if(empty($this->uniqueKeys)){
                    $this->foreignKeys = $foreignKeys;
                }else{
                    $this->foreignKeys = array_merge($this->foreignKeys, $foreignKeys);
                }
            } else {
                $this->foreignKeys[] = $foreignKeys;
            }
        }catch (\Exception $e){
            dd($this->foreignKeys, $foreignKeys, $this->getTable());
            dd($e->getMessage());
            die();
        }
    }

    /**
     * @return mixed
     */
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param mixed $autoIncrement
     */
    public function setAutoIncrement($autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return mixed
     */
    public function getAutoIncrementKeys()
    {
        return $this->autoIncrementKeys;
    }

    /**
     * @param mixed $autoIncrementKeys
     */
    public function setAutoIncrementKeys($autoIncrementKeys): void
    {
        $this->autoIncrementKeys = $autoIncrementKeys;
    }

    /**
     * @return mixed
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @param mixed $unique
     */
    public function setUnique($unique): void
    {
        $this->unique = $unique;
    }

    /**
     * @return mixed
     */
    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }

    /**
     * @param mixed $uniqueKeys
     */
    public function setUniqueKeys($uniqueKeys): void
    {
        if (is_array($uniqueKeys)) {
            if (empty($this->uniqueKeys)) {
                $this->uniqueKeys = $uniqueKeys;
            } else {
                $this->uniqueKeys = array_merge($this->uniqueKeys, $uniqueKeys);
            }
        } else {
            $this->uniqueKeys[] = $uniqueKeys;
        }
    }

    /**
     * @return mixed
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param mixed $index
     */
    public function setIndex($index): void
    {
        $this->index = $index;
    }

    /**
     * @return mixed
     */
    public function getIndexKeys()
    {
        return $this->indexKeys;
    }

    /**
     * @param mixed $indexKeys
     */
    public function setIndexKeys($indexKeys): void
    {
        if (is_array($indexKeys)) {
            if (empty($this->indexKeys)) {
                $this->indexKeys = $indexKeys;
            } else {
                $this->indexKeys = array_merge($this->indexKeys, $indexKeys);
            }
        } else {
            $this->indexKeys[] = $indexKeys;
        }
    }

    /**
     * @return mixed
     */
    public function getCol()
    {
        return $this->col;
    }

    /**
     * @param mixed $col
     */
    public function setCol($col): void
    {
        $this->col = $col;
    }

    /**
     * @return mixed
     */
    public function getNumberOfCols()
    {
        return $this->numberOfCols;
    }

    /**
     * @param mixed $numberOfCols
     */
    public function setNumberOfCols($numberOfCols): void
    {
        $this->numberOfCols = $numberOfCols;
    }

    /**
     * @return mixed
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param mixed $rows
     */
    public function setRows($rows): void
    {
        $this->rows = $rows;
    }

    /**
     * @return mixed
     */
    public function getNumberOfRows()
    {
        return $this->numberOfRows;
    }

    /**
     * @param mixed $numberOfRows
     */
    public function setNumberOfRows($numberOfRows): void
    {
        $this->numberOfRows = $numberOfRows;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param mixed $extra
     */
    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error): void
    {
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function getErrorSummary()
    {
        return $this->errorSummary;
    }

    /**
     * @param mixed $errorSummary
     */
    public function setErrorSummary($errorSummary): void
    {
        if (is_array($errorSummary)) {
            if (empty($this->errorSummary)) {
                $this->errorSummary = $errorSummary;
            } else {
                $this->errorSummary = array_merge($this->errorSummary, $errorSummary);
            }
        } else {
            $this->errorSummary[] = $errorSummary;
        }
    }


}