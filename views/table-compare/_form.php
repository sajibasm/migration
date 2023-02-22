<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\TableCompare $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="table-compare-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'tableName')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'isEngine')->textInput() ?>

    <?= $form->field($model, 'engineType')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'autoIncrement')->textInput() ?>

    <?= $form->field($model, 'autoIncrementKey')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'isPrimary')->textInput() ?>

    <?= $form->field($model, 'primaryKeys')->textInput() ?>

    <?= $form->field($model, 'isUnique')->textInput() ?>

    <?= $form->field($model, 'uniqueKeys')->textInput() ?>

    <?= $form->field($model, 'isIndex')->textInput() ?>

    <?= $form->field($model, 'indexKeys')->textInput() ?>

    <?= $form->field($model, 'maxType')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'maxValue')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'col')->textInput() ?>

    <?= $form->field($model, 'rows')->textInput() ?>

    <?= $form->field($model, 'columnStatics')->textInput() ?>

    <?= $form->field($model, 'isError')->textInput() ?>

    <?= $form->field($model, 'errorSummary')->textInput() ?>

    <?= $form->field($model, 'status')->textInput() ?>

    <?= $form->field($model, 'createdAt')->textInput() ?>

    <?= $form->field($model, 'processedAt')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
