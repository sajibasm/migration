<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\SyncTable $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sync Tables'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="sync-table-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'host',
            'dbName',
            'tableName',
            'isEngine',
            'engineType',
            'autoIncrement',
            'autoIncrementKey',
            'isPrimary',
            'primaryKeys:ntext',
            'isUnique',
            'uniqueKeys:ntext',
            'isIndex',
            'indexKeys:ntext',
            'maxColType',
            'maxColValue',
            'cols',
            'rows',
            'columnStatics:ntext',
            'isError',
            'errorSummary:ntext',
            'status',
            'createdAt',
            'processedAt',
        ],
    ]) ?>

</div>
