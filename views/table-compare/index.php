<?php

use app\models\TableCompare;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\models\TableCompareSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Table Compares');
$this->params['breadcrumbs'][] = $this->title;
Icon::map($this);
?>

<div class="row">
    <div class="col-lg-12">
        <div class="table-compare-index">
            <h1><?= Html::encode($this->title) ?></h1>
            <p>
                <?= Html::a(Yii::t('app', 'Create Table Compare'), ['pull'], ['class' => 'btn btn-success']) ?>
            </p>

            <?php Pjax::begin(); ?>
            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,

                'layout' => "{summary}\n{items}\n{pager}",
                'pager' => [
                    'class' => 'yii\bootstrap5\LinkPager',
                    'firstPageLabel' => 'First',
                    'lastPageLabel' => 'Last'
                ],
                //'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute' => 'tableName',
                        //'contentOptions' => ['style' => 'width: 100px;'],
                    ],
                    [
                        'attribute' => 'isEngine',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->isEngine ? Icon::show('check') : Icon::show('close');
                        }
                    ],
                    [
                        'attribute' => 'autoIncrement',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->autoIncrement ? Icon::show('check') : Icon::show('close');
                        }
                    ],
                    [
                        'attribute' => 'isPrimary',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->isPrimary ? Icon::show('check') : Icon::show('close');
                        }
                    ],
                    [
                        'attribute' => 'isUnique',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->isUnique ? Icon::show('check') : Icon::show('close');
                        }
                    ],
                    [
                        'attribute' => 'isIndex',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->isIndex ? Icon::show('check') : Icon::show('close');
                        }
                    ],

                    [
                        'attribute' => 'cols',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->cols ? Icon::show('check') : Icon::show('close');
                        }
                    ],

                    [
                        'attribute' => 'rows',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->rows ? Icon::show('check') : Icon::show('close');
                        }
                    ],
                    [
                        'attribute' => 'maxColType',
                        'format' => 'html',
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return $model->maxColType ? Icon::show('check') : Icon::show('close');
                        }
                    ],
                    [
                        'attribute' => 'isError',
                        'label' => 'Verdict',
                        'format' => 'html',
                        //'contentOptions' => ['style' => 'width: 95px;'],
                        'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                        'value' => function ($model) {
                            return !$model->isError ? '<span class="badge bg-success">' . Icon::show('check') . '</span>' : '<span class="badge bg-danger">' . Icon::show('close') . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'errorSummary',
                        'format' => 'html',
                        //'contentOptions' => ['style' => 'width: 95px;'],
                        'value' => function ($model) {
                            return !$model->isError ? '- All Ok' : implode("<br>", \yii\helpers\Json::decode($model->errorSummary));
                        }
                    ],

                    //'autoIncrementKey',
                    //'primaryKeys',
                    //'uniqueKeys',
                    //'indexKeys',
                    //'maxType',
                    //'maxValue',
                    //'col',
                    //'rows',
                    //'columnStatics',
                    //'isError',
                    //'errorSummary',
                    [
                        'attribute' => 'status',
                        'value' => function ($model) {
                            return TableCompare::STATUS_LABEL[$model->status];
                        }
                        //'contentOptions' => ['style' => 'width: 95px;'],
                    ],

                    //'processedAt',
//            [
//                'class' => ActionColumn::className(),
//                'urlCreator' => function ($action, TableCompare $model, $key, $index, $column) {
//                    return Url::toRoute([$action, 'id' => $model->id]);
//                 }
//            ],

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'contentOptions' => ['class' => 'text-center'],
                        'header' => 'Actions',
                        'template' => '{syn}',
                        //'visibleButtons'=>[],
                        'buttons' => [
                            'syn' => function ($url, $model, $key) {
                                if ($model->isError) {
                                    return Html::a(Icon::show('automation'), false, [
                                        'class' => 'pjax-syn-link btn btn-soft-success btn-sm',
                                        'syn-url' => Url::to(['syn', 'id' => $model->tableName], true),
                                        'pjax-container' => 'migrationPjax',
                                        'title' => Yii::t('yii', 'Update')
                                    ]);
                                }
                            },
                        ]
                    ],
                ],
            ]); ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>