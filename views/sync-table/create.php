<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SyncTable $model */

$this->title = Yii::t('app', 'Create Sync Table');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sync Tables'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sync-table-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
