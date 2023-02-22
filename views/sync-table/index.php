<?php

use app\models\SyncTable;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var app\models\SyncTableSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Sync Tables');
$this->params['breadcrumbs'][] = $this->title;
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
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'host',
            'dbName',
            'tableName',
            'isEngine',
            //'engineType',
            //'autoIncrement',
            //'autoIncrementKey',
            //'isPrimary',
            //'primaryKeys:ntext',
            //'isUnique',
            //'uniqueKeys:ntext',
            //'isIndex',
            //'indexKeys:ntext',
            //'maxColType',
            //'maxColValue',
            //'cols',
            //'rows',
            //'columnStatics:ntext',
            //'isError',
            //'errorSummary:ntext',
            //'status',
            //'createdAt',
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
