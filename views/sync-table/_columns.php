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
        "format" => 'html',
        'attribute' => 'isEngine',
//        'value' => function ($model) {
//            return $model->isEngine ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'autoIncrement',
//        'value' => function ($model) {
//            return $model->autoIncrement ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'isPrimary',
//        'value' => function ($model) {
//            return $model->isPrimary ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'isForeign',
//        'value' => function ($model) {
//            return $model->isForeign ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'isUnique',
//        'value' => function ($model) {
//            return $model->isUnique ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'isIndex',
//        'value' => function ($model) {
//            return $model->isIndex ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'isCols',
//        'value' => function ($model) {
//            return $model->isCols ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'isRows',
//        'value' => function ($model) {
//            return $model->isRows ? Icon::show('check') : Icon::show('times');
//        }
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        "format" => 'html',
        'attribute' => 'isSuccess',
        'label' => 'Success',
        //'value' => function ($model) {
            //return $model->isSuccess ? Icon::show('check') : Icon::show('times');
        //}
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
                if ($model->status === SyncTable::STATUS_SCHEMA_COMPLETED && !$model->isSuccess) {
                    return Html::a(Icon::show('cloud'), Url::to(['schema-sync', 'id' => $model->id]), [
                        'class' => 'btn btn-outline-secondary sync',
                        'data-pjax' => 0,
                        'title' => 'Schema Sync',
                        'data-url' => Url::to(['sync-table/schema-sync', 'id' => $model->id], true),
                    ]);
                }
            },

            'view' => function ($url, $model) {
                return Html::a(Icon::show('eye'), Url::to(['view', 'id' => $model->id]), [
                    'class' => 'btn btn-outline-success',
                    'data-pjax' => 0,
                    'title' => 'Details'
                ]);
            },
        ],
    ],
];
