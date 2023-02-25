<?php

use app\models\SyncConfig;
use app\models\SyncHostDb;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SyncTable $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="sync-table-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col">
            <?= $form->field($model, 'sourceDb')->dropDownList(ArrayHelper::map(SyncHostDb::find()->where(['type' => SyncConfig::TYPE_SOURCE])->orderBy('dbname')->all(), 'id', 'dbname')) ?>
        </div>
        <div class="col">
            <?= $form->field($model, 'destinationDb')->dropDownList(ArrayHelper::map(SyncHostDb::find()->where(['type' => SyncConfig::TYPE_DESTINATION])->orderBy('dbname')->all(), 'id', 'dbname')) ?>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
