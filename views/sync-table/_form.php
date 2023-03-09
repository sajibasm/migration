<?php

use app\models\SyncConfig;
use app\models\SyncHostDb;
use kartik\depdrop\DepDrop;
use kartik\select2\Select2;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\SyncTable $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="sync-table-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col">
            <?= $form->field($model, 'sourceHost')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(SyncConfig::find()->where(['type' => SyncConfig::TYPE_SOURCE])->all(), 'id', 'host'),
                'options' => ['placeholder' => 'Source Host'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
        <div class="col">
            <?= $form->field($model, 'sourceId')->widget(DepDrop::classname(), [
                'type' => DepDrop::TYPE_SELECT2,
                'pluginOptions' => [
                    'depends' => ['synctable-sourcehost'],
                    'placeholder' => 'Select...',
                    'url' => Url::to(['database', 'type' => SyncConfig::TYPE_SOURCE])
                ]
            ]);
            ?>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <?= $form->field($model, 'targetHost')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(SyncConfig::find()->where(['type' => SyncConfig::TYPE_TARGET])->all(), 'id', 'host'),
                'options' => ['placeholder' => 'Target Host'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>
        <div class="col">
            <?= $form->field($model, 'targetId')->widget(DepDrop::classname(), [
                'type' => DepDrop::TYPE_SELECT2,
                'pluginOptions' => [
                    'depends' => ['synctable-targethost'],
                    'placeholder' => 'Select...',
                    'url' => Url::to(['database', 'type' => SyncConfig::TYPE_TARGET])
                ]
            ]);
            ?>
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
