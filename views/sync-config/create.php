<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SyncConfig $model */

$this->title = Yii::t('app', 'Create Sync Config');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sync Configs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sync-config-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
