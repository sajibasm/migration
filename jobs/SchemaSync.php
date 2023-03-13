<?php

namespace app\jobs;
use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;

/**
 * Class SchemeInfoJob.
 */
class SchemaSync extends BaseObject implements RetryableJobInterface
{

    public $id;

    public $init_time;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        \app\components\MySqlSchemaResolver::createQueue($this->id, $this->init_time);
        //SchemaInfo::schemaQueue($this->id, $this->init_time);
    }

    /**
     * @inheritdoc
     */
    public function getTtr()
    {
        return 60;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error)
    {
        return $attempt < 3;
    }
}
