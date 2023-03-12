<?php
use app\models\SyncConfig;
use app\models\SyncTable;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

return [
    ['class' => 'kartik\grid\SerialColumn'],
    [
        'attribute' => 'dbType',
        'value' => function ($model) {
            return SyncConfig::DB_TYPE[$model->dbType];
        }
    ],
    [
        'attribute' => 'type',
        'value' => function ($model) {
            return SyncConfig::TYPE[$model->type];
        }
    ],
    'host',
    'dbname',
    'username',
    [
        'attribute' => 'status',
        'value' => function ($model) {
            return SyncConfig::STATUS[$model->status];
        }
    ],

    'createdAt',
    [
        'class' => 'kartik\grid\ActionColumn',
        'template' => ' {view} {update} {sync}',
        'options' => ['style' => 'width: 130px;'],
        'hAlign' => 'center',
        'header' => 'Action',
//        'urlCreator' => function ($action, $model, $key, $index) {
//            return Url::to(['view', 'id' => $model->id]);
//        },
        'buttons' => [
            'view' => function ($url, $model) {
                return Html::button(Icon::show('eye'), [
                    'type' => 'button',
                    'class' => 'btn btn-success',
                ]);
            },
            'update' => function ($url, $model) {
                return Html::button(Icon::show('edit'), [
                    'type' => 'button',
                    'class' => 'btn btn-primary',
                ]);
            },
            'sync' => function ($url, $model) {
                return Html::button(Icon::show('compare'), [
                    'type' => 'button',
                    'class' => 'btn btn-priary configSync',
                    'title' =>'Sync',
                    //'id' => 'config-sync-button',
                    'data-url' => Url::to(['sync-config/sync'], true),
                    'data-value' => $model->id,
                    //'data-confirm' => Yii::t('yii', 'Do you want to to sync this?'),
                    //'data-pjax'          => '0',
                    //'data-method' => 'post',
                ]);
            },
        ],
    ],
];
