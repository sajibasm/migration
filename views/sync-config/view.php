<?php

use app\models\SyncConfig;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\SyncConfig $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sync Configs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="sync-config-view">
    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
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
            'password',
            'charset',
            'createdAt',
            'updatedAt',
        ],
    ]) ?>

</div>
