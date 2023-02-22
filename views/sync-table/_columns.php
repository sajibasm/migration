<?php

use app\components\Constants;
use app\models\CompanyAccount;
use app\models\SyncTable;
use app\modules\AccessControl\components\Access;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

return [
    ['class' => 'kartik\grid\SerialColumn'],

    [
        'attribute'=>'source.host',
        'header'=>'Source DB',
        'value'=>function($model){
            return $model->source->dbname.'('.$model->source->host.')';
        }
    ],
    [
        'attribute'=>'destination.host',
        'header'=>'Destination DB',
        'value'=>function($model){
            return $model->destination->dbname.'('.$model->destination->host.')';
        }
    ],
    'tableName',
    [
        'attribute' => 'isEngine',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            //dd($model->isEngine);die();
            return $model->isEngine ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
    ],
    [
        'attribute' => 'autoIncrement',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            return $model->autoIncrement ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
    ],
    [
        'attribute' => 'isPrimary',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            return $model->isPrimary ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
    ],
    [
        'attribute' => 'isUnique',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            return $model->isUnique ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
    ],
    [
        'attribute' => 'isIndex',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            return $model->isIndex ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
    ],
    'maxColType',
    'maxColValue',
    [
        'attribute' => 'isCols',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            return !$model->cols ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
    ],

    [
        'attribute' => 'isRows',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            return !$model->rows ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
    ],
    [
        'attribute' => 'isError',
        'format' => 'html',
        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
        'value' => function ($model) {
            return !$model->isError ? Icon::show('check') : Icon::show('close',  ['style'=>'color: red;']);
        }
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
        'template' => ' {view} {update} {approve}',
        'options' => ['style' => 'width: 130px;'],
        'hAlign' => 'center',
        'header' => 'Action',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to(['view', 'id' => $model->id]);
        },
        'buttons' => [
//            'approve' => function ($url, $model) {
//                if ($model->status === Constants::STATUS_PENDING && Access::hasAction('approve')) {
//                    return Html::a('<span class="dripicons-checkmark"></span>', Url::to(['view', 'id' => $model->uuid, 'type' => Constants::MODAL_STATUS_APPROVE]), [
//                        'class' => 'btn btn-soft-info btn-sm waves-effect waves-light approveModal',
//                        'data-pjax' => 0,
//                        'data-bs-toggle'=>"tooltip",
//                        'data-bs-placement'=>"top",
//                        'data-bs-original-title'=>"Approve",
//                    ]);
//                }
//            },
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
