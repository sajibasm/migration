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

<?php
$js = <<<JS
    $(document).ready(function(){

        function checkStatus() {
            $(".sync").off().on("click", function(e){
                e.preventDefault();
                var url = $(this).attr("data-url");

                $.pjax.defaults.timeout = false;
                $.ajax({
                    url     : url,
                    type   : "post",
                    dataType: 'json',
                    success: function (response) {
                        // do something
                    }, error  : function () {
                        // do nothing
                    }
                });
            });
        }

        checkStatus();
    });
JS;

$this->registerJs($js);
?>

<div class="sync-table-index">
    <div class="table-responsive">
        <?= GridView::widget([
            'id' => 'sync-table',
            'dataProvider' => $dataProvider,
            'columns' => require '_columns.php',
            'headerContainer' => ['style' => 'top:10px', 'class' => 'kv-table-header'], // offset from top
            'floatHeader' => true, // table header floats when you scroll
            'floatPageSummary' => true, // table page summary floats when you scroll
            'floatFooter' => false, // disable floating of table footer
            'pjax' => true, // pjax is set to always false for this demo
            'pjaxSettings' => [
                'options' => [
                    'id' => 'configuration',
                    'enablePushState' => false,
                ]
            ],
            // parameters from the demo form
            'responsive' => false,
            'bordered' => false,
            'striped' => true,
            'condensed' => false,
            'hover' => true,
            'showPageSummary' => false,
            'panel' => [
                //'after' => '<div class="float-right float-end"><button type="button" class="btn btn-primary" onclick="var keys = $("#kv-grid-demo").yiiGridView("getSelectedRows").length; alert(keys > 0 ? "Downloaded " + keys + " selected books to your account." : "No rows selected for download.");"><i class="fas fa-download"></i> Download Selected</button></div><div style="padding-top: 5px;"><em>* The page summary displays SUM for first 3 amount columns and AVG for the last.</em></div><div class="clearfix"></div>',
                'heading' => Icon::show('retweet'),
                'type' => GridView::TYPE_ACTIVE,
                //'before' => '<div style="padding-top: 7px;"><em>* Resize table columns just like a spreadsheet by dragging the column edges.</em></div>',
            ],
            // set export properties
            'export' => [
                'fontAwesome' => true
            ],
//                            'exportConfig' => [
////                                'html' => [],
////                                'csv' => [],
////                                'txt' => [],
////                                'xls' => [],
////                                'pdf' => [],
////                                'json' => [],
//                            ],
            // set your toolbar
            'toolbar' => [
                [
                    'content' =>
                        Html::a(\kartik\icons\Icon::show('plus'), ['create'], [
                            'class' => 'btn btn-success',
                            'title' => Yii::t('app', 'Reset Grid'),
                            'data-pjax' => 0,
                        ]) . ' ' .
                        Html::a('<i class="fas fa-redo"></i>', ['index'], [
                            'class' => 'btn btn-outline-secondary',
                            'title' => Yii::t('app', 'Reset Grid'),
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
        ]); ?>
    </div>
</div>
