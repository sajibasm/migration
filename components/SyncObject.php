<?php

namespace app\components;

class SyncObject
{
    public $sourceHost;
    public $destinationHost;
    public $table;
    public $engine;
    public $engineType= '';

    public $primary;
    public $primaryKeys = '';
    public $foreign;
    public $foreignKeys = '';

    public $autoIncrement;
    public $autoIncrementKeys = '';

    public $unique;
    public $uniqueKeys = '';

    public $index;
    public $indexKeys = '';

    public $col;
    public $numberOfCols;

    public $rows;
    public $numberOfRows;
    public $max;
    public $maxType = '';
    public $maxValue = '';
    public $colInfo = [];

    public $error;
    public $errorSummary = [];

    public function __construct(){
        $this->engine = false;
        $this->primary = false;
        $this->foreign = false;
        $this->autoIncrement = false;
        $this->unique = false;
        $this->index = false;
        $this->col = false;
        $this->rows = false;
        $this->max = false;
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
        $this->primaryKeys = $primaryKeys;
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
        $this->foreignKeys = $foreignKeys;
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
        $this->uniqueKeys = $uniqueKeys;
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
        $this->indexKeys = $indexKeys;
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
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param mixed $max
     */
    public function setMax($max): void
    {
        $this->max = $max;
    }

    /**
     * @return mixed
     */
    public function getMaxType()
    {
        return $this->maxType;
    }

    /**
     * @param mixed $maxType
     */
    public function setMaxType($maxType): void
    {
        $this->maxType = $maxType;
    }

    /**
     * @return mixed
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @param mixed $maxValue
     */
    public function setMaxValue($maxValue): void
    {
        $this->maxValue = $maxValue;
    }

    /**
     * @return mixed
     */
    public function getColInfo()
    {
        return $this->colInfo;
    }

    /**
     * @param mixed $colInfo
     */
    public function setColInfo($colInfo): void
    {
        $this->colInfo = $colInfo;
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
        if(is_array($errorSummary)){
            $this->errorSummary[] = $errorSummary;
        }else{
            $this->errorSummary[] = $errorSummary;
        }
    }


}