<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\SyncTable $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="sync-table-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'host')->textInput() ?>

    <?= $form->field($model, 'dbName')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'tableName')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'isEngine')->textInput() ?>

    <?= $form->field($model, 'engineType')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'autoIncrement')->textInput() ?>

    <?= $form->field($model, 'autoIncrementKey')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'isPrimary')->textInput() ?>

    <?= $form->field($model, 'primaryKeys')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'isUnique')->textInput() ?>

    <?= $form->field($model, 'uniqueKeys')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'isIndex')->textInput() ?>

    <?= $form->field($model, 'indexKeys')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'maxColType')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'maxColValue')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'cols')->textInput() ?>

    <?= $form->field($model, 'rows')->textInput() ?>

    <?= $form->field($model, 'columnStatics')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'isError')->textInput() ?>

    <?= $form->field($model, 'errorSummary')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'status')->textInput() ?>

    <?= $form->field($model, 'createdAt')->textInput() ?>

    <?= $form->field($model, 'processedAt')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
