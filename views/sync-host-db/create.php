<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\SyncHostDb $model */

$this->title = Yii::t('app', 'Create Sync Host Db');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sync Host Dbs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sync-host-db-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
