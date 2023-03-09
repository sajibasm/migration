<?php

namespace app\components;

class Engine
{

    public $schemaName;
    public $tableType;

    public $name;
    public $version;
    public $rowFormat;
    public $tableRows;
    public $avgRowLength;
    public $dataLength;
    public $maxDataLength;
    public $indexLength;
    public $dataFree;
    public $autoIncrement;
    public $createdTime;
    public $updatedTime;
    public $checkTime;
    public $tableCollation;


    /**
     * @param $schemaName
     */
    public function __construct($schema)
    {
        if(is_array($schema)){
            $this->schemaName = isset($schema['TABLE_SCHEMA'])?$schema['TABLE_SCHEMA']:'';
            $this->tableType = isset($schema['TABLE_TYPE'])?$schema['TABLE_TYPE']:'';
            $this->name = isset($schema['ENGINE'])?$schema['ENGINE']:'';
            $this->version = isset($schema['VERSION'])?$schema['VERSION']:'';
            $this->rowFormat = isset($schema['ROW_FORMAT'])?$schema['ROW_FORMAT']:'';
            $this->tableRows = isset($schema['TABLE_ROWS'])?$schema['TABLE_ROWS']:'';
            $this->avgRowLength = isset($schema['AVG_ROW_LENGTH'])?$schema['AVG_ROW_LENGTH']:'';
            $this->dataLength = isset($schema['DATA_LENGTH'])?$schema['DATA_LENGTH']:'';
            $this->maxDataLength = isset($schema['MAX_DATA_LENGTH'])?$schema['MAX_DATA_LENGTH']:'';
            $this->indexLength = isset($schema['INDEX_LENGTH'])?$schema['INDEX_LENGTH']:'';
            $this->dataFree = isset($schema['DATA_FREE'])?$schema['DATA_FREE']:'';
            $this->autoIncrement = isset($schema['AUTO_INCREMENT'])?$schema['AUTO_INCREMENT']:'';
            $this->createdTime = isset($schema['CREATE_TIME'])?$schema['CREATE_TIME']:'';
            $this->updatedTime = isset($schema['UPDATE_TIME'])?$schema['UPDATE_TIME']:'';
            $this->checkTime = isset($schema['CHECK_TIME'])?$schema['CHECK_TIME']:'';
            $this->tableCollation = isset($schema['TABLE_COLLATION'])?$schema['TABLE_COLLATION']:'';
        }
    }


}