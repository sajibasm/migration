<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\SyncTable;

/**
 * SyncTableSearch represents the model behind the search form of `app\models\SyncTable`.
 */
class SyncTableSearch extends SyncTable
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'host', 'isEngine', 'autoIncrement', 'isPrimary', 'isUnique', 'isIndex', 'cols', 'rows', 'isError', 'status'], 'integer'],
            [['dbName', 'tableName', 'engineType', 'autoIncrementKey', 'primaryKeys', 'uniqueKeys', 'indexKeys', 'maxColType', 'maxColValue', 'columnStatics', 'errorSummary', 'createdAt', 'processedAt'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = SyncTable::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'host' => $this->host,
            'isEngine' => $this->isEngine,
            'autoIncrement' => $this->autoIncrement,
            'isPrimary' => $this->isPrimary,
            'isUnique' => $this->isUnique,
            'isIndex' => $this->isIndex,
            'cols' => $this->cols,
            'rows' => $this->rows,
            'isError' => $this->isError,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'processedAt' => $this->processedAt,
        ]);

        $query->andFilterWhere(['like', 'dbName', $this->dbName])
            ->andFilterWhere(['like', 'tableName', $this->tableName])
            ->andFilterWhere(['like', 'engineType', $this->engineType])
            ->andFilterWhere(['like', 'autoIncrementKey', $this->autoIncrementKey])
            ->andFilterWhere(['like', 'primaryKeys', $this->primaryKeys])
            ->andFilterWhere(['like', 'uniqueKeys', $this->uniqueKeys])
            ->andFilterWhere(['like', 'indexKeys', $this->indexKeys])
            ->andFilterWhere(['like', 'maxColType', $this->maxColType])
            ->andFilterWhere(['like', 'maxColValue', $this->maxColValue])
            ->andFilterWhere(['like', 'columnStatics', $this->columnStatics])
            ->andFilterWhere(['like', 'errorSummary', $this->errorSummary]);

        return $dataProvider;
    }
}