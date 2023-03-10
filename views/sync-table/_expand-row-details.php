<?php

use app\models\SyncTable;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\SyncTable $model */
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sync-table-view">
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
//            [
//                'attribute' => 'source.host',
//                'label' => 'Source DB',
//                'value' => function ($model) {
//                    return $model->source->dbname . '/' . $model->source->host;
//                }
//            ],
//            [
//                'attribute' => 'destination.host',
//                'label' => 'Destination DB',
//                'value' => function ($model) {
//                    return $model->destination->dbname . '/' . $model->destination->host ;
//                }
//            ],
            [
                'attribute' => 'errorSummary',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->status === SyncTable::STATUS_SCHEMA_COMPLETED) {
                        if (!$model->isSuccess) {
                            return implode("<br>", \yii\helpers\Json::decode($model->errorSummary));
                        }
                        return "Everything seems to be ok.";
                    }
                },
            ],
        ],
    ]) ?>
</div>
