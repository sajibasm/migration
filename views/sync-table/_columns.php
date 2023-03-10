<?php

use app\models\SyncTable;
use kartik\grid\GridView;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\SyncTable $model */

return [
    ['class' => 'kartik\grid\SerialColumn'],
    [
        'class' => 'kartik\grid\ExpandRowColumn',
        'width' => '50px',
        'value' => function ($model, $key, $index, $column) {
            return GridView::ROW_COLLAPSED;
        },
        // uncomment below and comment detail if you need to render via ajax
        // 'detailUrl' => Url::to(['/site/book-details']),
        'detail' => function ($model, $key, $index, $column) {
            return Yii::$app->controller->renderPartial('_expand-row-details', ['model' => $model]);
        },
        'headerOptions' => ['class' => 'kartik-sheet-style'],
        'expandOneOnly' => true
    ],
    [
        'attribute' => 'source.host',
        'header' => 'Source',
        'value' => function ($model) {
            return isset($model->source->dbname) ?: null;
        }
    ],
    [
        'attribute' => 'destination.host',
        'header' => 'Target',
        'value' => function ($model) {
            return isset($model->destination->dbname) ?: null;
        }
    ],
    'tableName',
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isEngine',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'autoIncrement',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isPrimary',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isForeign',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isUnique',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isIndex',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isCols',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isRows',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isSuccess',
        'label' => 'Success',
    ],
    [
        'attribute' => 'status',
        'value' => function ($model) {
            return SyncTable::STATUS_LABEL[$model->status];
        }
    ],

    'createdAt',
    [
        'class' => 'kartik\grid\ActionColumn',
        'template' => ' {view} {sync}',
        'options' => ['style' => 'width: 130px;'],
        'hAlign' => 'center',
        'header' => 'Action',
//        'urlCreator' => function ($action, $model, $key, $index) {
//            return Url::to(['view', 'id' => $model->id]);
//        },
        'buttons' => [
            'sync' => function ($url, $model) {
                if ($model->status=== SyncTable::STATUS_SCHEMA_COMPLETED && !$model->isSuccess) {
                    return Html::a(Icon::show('cloud'), Url::to(['schema-sync', 'id' => $model->id]), [
                        'class' => 'btn btn-outline-secondary',
                        'data-pjax' => 0,
                         'title' =>'Schema Sync'
                    ]);
                }
            },

            'view' => function ($url, $model) {
                    return Html::a(Icon::show('eye'), Url::to(['view', 'id' => $model->id]), [
                        'class' => 'btn btn-outline-success',
                        'data-pjax' => 0,
                        'title' =>'Details'
                    ]);
            },
        ],
    ],
];
