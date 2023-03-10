<?php

namespace app\jobs;

use app\components\DynamicConnection;
use app\components\SyncUtility;
use app\models\SyncTable;
use Exception;
use Yii;
use yii\helpers\Json;

/**
 * Class SchemeInfoJob.
 */
class SchemeInfoJob extends \yii\base\BaseObject implements \yii\queue\RetryableJobInterface
{
    public $table;

    public $limit;

    public $init_time;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        SyncUtility::schemaQueue($this->limit, $this->init_time);
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
