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
            [['id', 'sourceDb', 'destinationDb', 'isEngine', 'autoIncrement', 'isPrimary', 'isUnique', 'isIndex', 'isCols', 'isRows', 'isSuccess'], 'integer'],
            [['status'], 'integer'],
            [['tableName', 'extra', 'errorSummary', 'createdAt', 'processedAt'], 'safe'],
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
            'sourceDb' => $this->sourceDb,
            'destinationDb' => $this->destinationDb,
            'isEngine' => $this->isEngine,
            'autoIncrement' => $this->autoIncrement,
            'isPrimary' => $this->isPrimary,
            'isUnique' => $this->isUnique,
            'isIndex' => $this->isIndex,
            'isCols' => $this->isCols,
            'isRows' => $this->isRows,
            'isSuccess' => $this->isSuccess,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'processedAt' => $this->processedAt,
        ]);

        $query->andFilterWhere(['like', 'tableName', $this->tableName])
            ->andFilterWhere(['like', 'extra', $this->extra])
            ->andFilterWhere(['like', 'errorSummary', $this->errorSummary]);

        return $dataProvider;
    }
}
