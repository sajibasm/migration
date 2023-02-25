<?php

use app\models\SyncConfig;
use app\models\SyncHostDb;
use app\models\SyncTable;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

return [
    ['class' => 'kartik\grid\SerialColumn'],
    'host',
    [
        'attribute' => 'type',
        'value' => function ($model) {
            return SyncHostDb::TYPE[$model->type];
        }
    ],
    'dbname',
    'createdAt',
];
