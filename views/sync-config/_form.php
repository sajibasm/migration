<?php

use app\models\SyncConfig;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\SyncConfig $model */
/** @var yii\bootstrap5\ActiveForm $form */
?>

<div class="sync-config-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'dbType')->dropDownList(SyncConfig::DB_TYPE) ?>

    <?= $form->field($model, 'type')->dropDownList(SyncConfig::TYPE) ?>

    <?= $form->field($model, 'host')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'dbname')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'charset')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->dropDownList(SyncConfig::STATUS) ?>


    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
