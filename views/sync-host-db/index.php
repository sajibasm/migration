<?php

use app\models\SyncHostDb;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var app\models\SyncHostDbSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Sync Host Dbs');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sync-host-db-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create Sync Host DB'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'pager' => [
            'class' => 'yii\bootstrap5\LinkPager',
            'firstPageLabel' => 'First',
            'lastPageLabel' => 'Last'
        ],
        //'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'host',
            'dbname',
            [
                'attribute' => 'type',
                'value' => function ($model) {
                    return SyncHostDb::TYPE[$model->type];
                }
            ],
            'createdAt',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, SyncHostDb $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
