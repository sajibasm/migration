<?php

use app\models\SyncTable;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var app\models\SyncTableSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Sync Tables');
$this->params['breadcrumbs'][] = $this->title;
Icon::map($this);
?>
<div class="sync-table-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create Sync Table'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        //'filterModel' => $searchModel,
        'pager' => [
            'class' => 'yii\bootstrap5\LinkPager',
            'firstPageLabel' => 'First',
            'lastPageLabel' => 'Last'
        ],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                    'attribute'=>'source.host',
                    'header'=>'Source Host',
            ],
            [
                'attribute'=>'destination.host',
                'header'=>'Destination Host',
            ],

            'tableName',
            [
                'attribute' => 'isEngine',
                'format' => 'html',
                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                'value' => function ($model) {
                    //dd($model->isEngine);die();
                    return $model->isEngine ? Icon::show('check') : Icon::show('close');
                }
            ],
            //'engineType',
            [
                'attribute' => 'autoIncrement',
                'format' => 'html',
                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                'value' => function ($model) {
                    return $model->autoIncrement ? Icon::show('check') : Icon::show('close');
                }
            ],
            //'autoIncrementKey',
//            [
//                'attribute' => 'autoIncrementKey',
//                'format' => 'html',
//                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 10px;'],
//            ],
            [
                'attribute' => 'isPrimary',
                'format' => 'html',
                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                'value' => function ($model) {
                    return $model->isPrimary ? Icon::show('check') : Icon::show('close');
                }
            ],
            //'primaryKeys',

            [
                'attribute' => 'isUnique',
                'format' => 'html',
                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                'value' => function ($model) {
                    return $model->isUnique ? Icon::show('check') : Icon::show('close');
                }
            ],
            //'uniqueKeys',
            [
                'attribute' => 'isIndex',
                'format' => 'html',
                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                'value' => function ($model) {
                    return $model->isIndex ? Icon::show('check') : Icon::show('close');
                }
            ],
            //'indexKeys',
            'maxColType',
            'maxColValue',
            'cols',
            'rows',
            [
                'attribute' => 'isError',
                'format' => 'html',
                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                'value' => function ($model) {
                    return !$model->isError ? '<span class="badge bg-success">' . Icon::show('check') . '</span>' : '<span class="badge bg-danger">' . Icon::show('close') . '</span>';
                }
            ],
            [
                'attribute' => 'errorSummary',
                'format' => 'html',
                'contentOptions' => ['style' => 'width: 100px;'],
                'value' => function ($model) {
                    return !$model->isError ? '- All Ok' : implode("<br>", Json::decode($model->errorSummary));
                }
            ],
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    return SyncTable::STATUS_LABEL[$model->status];
                }
                //'contentOptions' => ['style' => 'width: 95px;'],
            ],

            'createdAt',
            //'processedAt',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, SyncTable $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
