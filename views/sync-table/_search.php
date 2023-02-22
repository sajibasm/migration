<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\SyncTableSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="sync-table-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'sourceDb') ?>

    <?= $form->field($model, 'destinationDb') ?>

    <?= $form->field($model, 'tableName') ?>

    <?= $form->field($model, 'isEngine') ?>

    <?php // echo $form->field($model, 'engineType') ?>

    <?php // echo $form->field($model, 'autoIncrement') ?>

    <?php // echo $form->field($model, 'autoIncrementKey') ?>

    <?php // echo $form->field($model, 'isPrimary') ?>

    <?php // echo $form->field($model, 'primaryKeys') ?>

    <?php // echo $form->field($model, 'isUnique') ?>

    <?php // echo $form->field($model, 'uniqueKeys') ?>

    <?php // echo $form->field($model, 'isIndex') ?>

    <?php // echo $form->field($model, 'indexKeys') ?>

    <?php // echo $form->field($model, 'maxColType') ?>

    <?php // echo $form->field($model, 'maxColValue') ?>

    <?php // echo $form->field($model, 'cols') ?>

    <?php // echo $form->field($model, 'rows') ?>

    <?php // echo $form->field($model, 'columnStatics') ?>

    <?php // echo $form->field($model, 'isError') ?>

    <?php // echo $form->field($model, 'errorSummary') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'createdAt') ?>

    <?php // echo $form->field($model, 'processedAt') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
