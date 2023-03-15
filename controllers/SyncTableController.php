<?php

namespace app\controllers;

use app\components\MysqlSchemaConflict;
use app\jobs\SchemaSync;
use app\jobs\SchemeInfoJob;
use app\models\SyncHostDb;
use app\models\SyncTable;
use app\models\SyncTableSearch;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * SyncTableController implements the CRUD actions for SyncTable model.
 */
class SyncTableController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'create', 'view', 'update', 'delete'],
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'view', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all SyncTable models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SyncTableSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SyncTable model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }


    public function actionDatabase($type){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $parents = $_POST['depdrop_parents'];
            if ($parents != null) {
                $config = $parents[0];
                $models = SyncHostDb::find()->where(['type' => $type, 'config'=>$config])->orderBy('dbname')->all();
                foreach ($models as $model) {
                    $out[] = ['id'=>$model->id, 'name'=>$model->dbname];
                }

                return ['output'=>$out, 'selected'=>''];
            }
        }
        return ['output'=>'', 'selected'=>''];

    }

    /**
     * Creates a new SyncTable model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new SyncTable();
        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                if (MysqlSchemaConflict::saveTableMetaQueue($model->source, $model->target)) {
                    Yii::$app->getCache()->flush();
                    Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time'=> microtime(true)]));
                    Yii::$app->getSession()->setFlash('success', 'Table meta queue has been created successfully');
                } else {
                    Yii::$app->getSession()->setFlash('error', 'Unable to create able meta queue');
                }
                return $this->redirect(['index']);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }


    public function actionSchemaSync($id)
    {
        if($id){
            //Yii::$app->queue->push(new SchemaSync(['id' => $id, 'init_time'=> microtime(true)]));
            //SchemaInfo::schemaQueue(10, microtime(true));
            \app\components\MySqlSchemaResolver::createQueue($id, microtime(true));
            return true;
        }
    }


    public function actionSchemaQueue()
    {
        MysqlSchemaConflict::createQueue(10, microtime(true));
        //Yii::$app->queue->push(new SchemeInfoJob(['limit' => 20, 'init_time'=> microtime(true)]));
    }


    /**
     * Updates an existing SyncTable model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing SyncTable model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the SyncTable model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return SyncTable the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SyncTable::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
