<?php use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;
use yii\widgets\Pjax;

$this->title = Yii::t('app', 'MigrationSearch Script');
$this->params['breadcrumbs'][] = $this->title;

//dd($dataProvider);
$script = "
 //$('.pjax-syn-link').on('click', function() {
     $('body').on('click','.pjax-syn-link',function(e){
     
            e.preventDefault();
            var synUrl = $(this).attr('syn-url');
            var pjaxContainer = $(this).attr('pjax-container');         
            var result = confirm('Delete this item, are you sure?');                                
            if(result) {
                $.ajax({
                    url: synUrl,
                    type: 'post',
                    error: function(xhr, status, error) {
                        alert('There was an error with your request.' + xhr.responseText);
                    }
                }).done(function(data) {
                    $.pjax.reload({container:'#migrationPjax', async: true, timeout:3000});
                });
            }
        });

    //$(document).on('ready pjax:success', function() {          
    //});";

$this->registerJs($script, View::POS_READY, 'Pjax');

?>


<div class="migration-index">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">

                <div class="card-header">
                    <h4 class="card-title"><?= Html::encode($this->title) ?></h4>
                </div>

                <div class="card-body">
                    <div class="table-responsive all">
                        <?php Pjax::begin(['id' => 'migrationPjax', 'enablePushState' => true, 'clientOptions' => ['method' => 'POST']]) ?>
                        <?php echo GridView::widget([
                            'showHeader' => true,
                            'dataProvider' => $dataProvider,
                            'layout' => "{summary}\n{items}\n{pager}",
                            'pager' => [
                                'class' => 'yii\bootstrap5\LinkPager',
                                'firstPageLabel' => 'First',
                                'lastPageLabel' => 'Last'
                            ],
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'table',
                                    //'contentOptions' => ['style' => 'width: 30px;'],
                                ],
                                [
                                    'attribute' => 'engine',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->engine ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'ai',
                                    'label' => 'AI',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->ai ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'primary',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->primary ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'unique',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->unique ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'index',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->index ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'cols',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->cols ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'rows',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->rows ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'maxId',
                                    'label' => 'MaxId',
                                    'format' => 'html',
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return $model->maxId ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>';
                                    }
                                ],
                                [
                                    'attribute' => 'error',
                                    'label' => 'Verdict',
                                    'format' => 'html',
                                    //'contentOptions' => ['style' => 'width: 95px;'],
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'width: 5px;'],
                                    'value' => function ($model) {
                                        return (bool)$model->error ? '<span class="badge bg-success"><i class="fa fa-check" aria-hidden="true"></i></span>' : '<span class="badge bg-danger"><i class="fa fa-times" aria-hidden="true"></i></span>';
                                    }
                                ],
                                [
                                    'attribute' => 'ErrorSummary',
                                    'format' => 'html',
                                    'value' => function ($model) {
                                        return $model->error ? '- All Good' : $model->errorSummary;
                                    }
                                    //'contentOptions' => ['style' => 'width: 95px;'],
                                ],
                                [
                                    'class' => 'yii\grid\ActionColumn',
                                    'contentOptions' => ['class' => 'text-center'],
                                    'header' => 'Actions',
                                    'template' => '{syn}',
                                    //'visibleButtons'=>[],
                                    'buttons' => [
                                        'syn' => function ($url, $model, $key) {
                                            if ($model->error === false) {
                                                return Html::a('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);transform: ;msFilter:;"><path d="M2 12h2a7.986 7.986 0 0 1 2.337-5.663 7.91 7.91 0 0 1 2.542-1.71 8.12 8.12 0 0 1 6.13-.041A2.488 2.488 0 0 0 17.5 7C18.886 7 20 5.886 20 4.5S18.886 2 17.5 2c-.689 0-1.312.276-1.763.725-2.431-.973-5.223-.958-7.635.059a9.928 9.928 0 0 0-3.18 2.139 9.92 9.92 0 0 0-2.14 3.179A10.005 10.005 0 0 0 2 12zm17.373 3.122c-.401.952-.977 1.808-1.71 2.541s-1.589 1.309-2.542 1.71a8.12 8.12 0 0 1-6.13.041A2.488 2.488 0 0 0 6.5 17C5.114 17 4 18.114 4 19.5S5.114 22 6.5 22c.689 0 1.312-.276 1.763-.725A9.965 9.965 0 0 0 12 22a9.983 9.983 0 0 0 9.217-6.102A9.992 9.992 0 0 0 22 12h-2a7.993 7.993 0 0 1-.627 3.122z"></path><path d="M12 7.462c-2.502 0-4.538 2.036-4.538 4.538S9.498 16.538 12 16.538s4.538-2.036 4.538-4.538S14.502 7.462 12 7.462zm0 7.076c-1.399 0-2.538-1.139-2.538-2.538S10.601 9.462 12 9.462s2.538 1.139 2.538 2.538-1.139 2.538-2.538 2.538z"></path></svg>', false, [
                                                    'class' => 'pjax-syn-link btn btn-soft-success btn-sm waves-effect waves-light',
                                                    'syn-url' => Url::to(['syn', 'id' => $model->table], true),
                                                    'pjax-container' => 'migrationPjax',
                                                    'title' => Yii::t('yii', 'Update')
                                                ]);
                                            }
                                        },
                                    ]
                                ],
                            ],
                        ]); ?>
                        <?php Pjax::end() ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
