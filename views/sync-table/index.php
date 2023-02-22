<?php

use app\models\SyncTable;
use kartik\grid\GridView;
use kartik\icons\FontAwesomeAsset;
use kartik\icons\Icon;
use yii\helpers\Html;


/** @var yii\web\View $this */
/** @var app\models\SyncTableSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Sync Tables');
$this->params['breadcrumbs'][] = $this->title;
Icon::map($this);
FontAwesomeAsset::register($this);
?>
<div class="sync-table-index">

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><?= Html::encode($this->title) ?></h4>

                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php
                        echo GridView::widget([
                            'id' => 'sync-table',
                            'dataProvider' => $dataProvider,
                            //'filterModel' => $searchModel,
                            'columns' => require '_columns.php',
                            'headerContainer' => ['style' => 'top:50px', 'class' => 'kv-table-header'], // offset from top
                            'floatHeader' => true, // table header floats when you scroll
                            'floatPageSummary' => true, // table page summary floats when you scroll
                            'floatFooter' => false, // disable floating of table footer
                            'pjax' => false, // pjax is set to always false for this demo
                            // parameters from the demo form
                            'responsive' => false,
                            'bordered' => true,
                            'striped' => false,
                            'condensed' => true,
                            'hover' => true,
                            'showPageSummary' => true,
                            'panel' => [
                                'after' => '<div class="float-right float-end"><button type="button" class="btn btn-primary" onclick="var keys = $("#kv-grid-demo").yiiGridView("getSelectedRows").length; alert(keys > 0 ? "Downloaded " + keys + " selected books to your account." : "No rows selected for download.");"><i class="fas fa-download"></i> Download Selected</button></div><div style="padding-top: 5px;"><em>* The page summary displays SUM for first 3 amount columns and AVG for the last.</em></div><div class="clearfix"></div>',
                                'heading' => '<i class="fas fa-book"></i>  Library',
                                'type' => 'primary',
                                'before' => '<div style="padding-top: 7px;"><em>* Resize table columns just like a spreadsheet by dragging the column edges.</em></div>',
                            ],
                            // set export properties
                            'export' => [
                                'fontAwesome' => true
                            ],
                            'exportConfig' => [
//                                'html' => [],
//                                'csv' => [],
//                                'txt' => [],
//                                'xls' => [],
//                                'pdf' => [],
//                                'json' => [],
                            ],
                            // set your toolbar
                            'toolbar' =>  [
                                [
                                    'content' =>
                                        Html::button('<i class="fas fa-plus"></i>', [
                                            'class' => 'btn btn-success',
                                            'title' => Yii::t('app', 'Add Book'),
                                            'onclick' => 'alert("This should launch the book creation form.\n\nDisabled for this demo!");'
                                        ]) . ' '.
                                        Html::a('<i class="fas fa-redo"></i>', ['grid-demo'], [
                                            'class' => 'btn btn-outline-secondary',
                                            'title'=>Yii::t('app', 'Reset Grid'),
                                            'data-pjax' => 0,
                                        ]),
                                    'options' => ['class' => 'btn-group mr-2 me-2']
                                ],
                                '{export}',
                                '{toggleData}',
                            ],
                            'toggleDataContainer' => ['class' => 'btn-group mr-2 me-2'],
                            'persistResize' => false,
                            'toggleDataOptions' => ['minCount' => 10],
                            'itemLabelSingle' => 'book',
                            'itemLabelPlural' => 'books'
                        ]);

                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
