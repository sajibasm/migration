<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TableCompare;

/**
 * TableCompareSearch represents the model behind the search form of `app\models\TableCompare`.
 */
class TableCompareSearch extends TableCompare
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'isEngine', 'autoIncrement', 'isPrimary', 'isUnique', 'isIndex', 'cols', 'rows', 'isError', 'status'], 'integer'],
            [['tableName', 'engineType', 'autoIncrementKey', 'primaryKeys', 'uniqueKeys', 'indexKeys', 'maxType', 'maxValue', 'columnStatics', 'errorSummary', 'createdAt', 'processedAt'], 'safe'],
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
        $query = TableCompare::find();

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

        $query->andFilterWhere(['like', 'tableName', $this->tableName])
            ->andFilterWhere(['like', 'engineType', $this->engineType])
            ->andFilterWhere(['like', 'autoIncrementKey', $this->autoIncrementKey])
            ->andFilterWhere(['like', 'primaryKeys', $this->primaryKeys])
            ->andFilterWhere(['like', 'uniqueKeys', $this->uniqueKeys])
            ->andFilterWhere(['like', 'indexKeys', $this->indexKeys])
            ->andFilterWhere(['like', 'maxType', $this->maxType])
            ->andFilterWhere(['like', 'maxValue', $this->maxValue])
            ->andFilterWhere(['like', 'columnStatics', $this->columnStatics])
            ->andFilterWhere(['like', 'errorSummary', $this->errorSummary]);

        return $dataProvider;
    }
}
