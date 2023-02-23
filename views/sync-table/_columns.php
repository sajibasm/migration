<?php

use app\components\Constants;
use app\models\CompanyAccount;
use app\models\SyncTable;
use app\modules\AccessControl\components\Access;
use kartik\grid\GridView;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

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
        'header' => 'Source DB',
        'value' => function ($model) {
            return $model->source->dbname . '(' . $model->source->host . ')';
        }
    ],
    [
        'attribute' => 'destination.host',
        'header' => 'Destination DB',
        'value' => function ($model) {
            return $model->destination->dbname . '(' . $model->destination->host . ')';
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
        'attribute' => 'isUnique',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'vAlign' => 'middle',
        'attribute' => 'isIndex',
    ],
    'maxColType',
    'maxColValue',
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
        'attribute' => 'isError',
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
        'template' => ' {view} {update} {sync}',
        'options' => ['style' => 'width: 130px;'],
        'hAlign' => 'center',
        'header' => 'Action',
//        'urlCreator' => function ($action, $model, $key, $index) {
//            return Url::to(['view', 'id' => $model->id]);
//        },
        'buttons' => [
            'sync' => function ($url, $model) {
                return Html::a(Icon::show('cloud'), Url::to(['view', 'id' => $model->id]), [
                    'class' => 'btn btn-soft-info btn-sm waves-effect waves-light approveModal',
                    'data-pjax' => 0,
                    'data-bs-toggle' => "tooltip",
                    'data-bs-placement' => "top",
                    'data-bs-original-title' => "Approve",
                ]);
            },
//            'view' => function ($url, $model) {
//                if (Access::hasAction('view')) {
//                    return Html::a('<span class="bx bx-show"></span>', Url::to(['view', 'id' => $model->uuid]), [
//                        'class' => 'btn btn-soft-primary btn-sm waves-effect waves-light',
//                        'data-pjax' => 0,
//                        'data-bs-toggle' => "tooltip",
//                        'data-bs-placement' => "top",
//                        'data-bs-original-title' => "View",
//                    ]);
//                }
//            },
//            'update' => function ($url, $model) {
//                if (Access::hasAction('update') && $model->status===Constants::STATUS_APPROVED) {
//                    return Html::a('<span class="dripicons-document-edit"></span>', Url::to(['update', 'id' => $model->uuid]), [
//                        'class' => 'btn btn-soft-warning btn-sm waves-effect waves-light',
//                        'data-pjax' => 0,
//                        'data-bs-toggle' => "tooltip",
//                        'data-bs-placement' => "top",
//                        'data-bs-original-title' => "Update",
//                    ]);
//                }
//            },
        ],
    ],
];
