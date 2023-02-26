<?php

use yii\db\Migration;

/**
 * Class m230221_142136_syncTable
 */
class m230221_142136_syncTable extends Migration
{

    public function safeUp()
    {
        echo "m230220_072109_create_synctable began....\n";
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('sync_table', [
            'id' => $this->primaryKey(),
            'sourceDb' => $this->integer()->notNull(),
            'destinationDb' => $this->integer()->notNull(),
            'tableName' => $this->string(100)->notNull(),
            'isEngine' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'engineType' => $this->string(10)->null(),
            'autoIncrement' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'autoIncrementKey' => $this->string(20)->null(),
            'isPrimary' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'primaryKeys' => $this->text()->null(),
            'isForeignKey' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'foreignKey' => $this->text()->null(),
            'isUnique' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'uniqueKeys' => $this->text()->null(),
            'isIndex' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'indexKeys' => $this->text()->null(),
            'isMax' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'maxColType' => $this->string(20),
            'maxColValue' => $this->string(50)->null(),
            'isCols' => $this->smallInteger(20)->defaultValue(0),
            'numberOfCols' => $this->integer(20)->defaultValue(0),
            'isRows' => $this->smallInteger(20)->defaultValue(0),
            'numberOfRows' => $this->integer(50)->defaultValue(0),
            'columnStatics' => $this->text()->null(),
            'isError' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'errorSummary' => $this->text()->null(),
            'status' => $this->smallInteger(1)->defaultValue(0)->notNull()->comment('0=Pull, 1=Schema_Sync, 2=Data_Sync, 9=Processed'),
            'createdAt' => $this->timestamp()->defaultExpression('NOW()')->notNull(),
            'processedAt' => $this->timestamp()->defaultExpression('NOW()')->notNull()
        ],  $tableOptions);
        $this->createIndex('combinedUnique', 'sync_table', ['sourceDb', 'destinationDb', 'tableName'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('sync_table');
        echo "m230221_142136_syncTable cannot be reverted.\n";
        return false;
    }

}
