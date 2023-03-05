<?php

namespace app\components;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class TableMetaQueueJob extends BaseObject implements JobInterface
{
    public function execute($queue)
    {
        SyncUtility::queue(10);
    }
}