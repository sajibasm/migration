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
            'isEngine'=>$this->smallInteger(1)->defaultValue(0)->notNull(),
            'engine'=>$this->string(20)->null(),
            'autoIncrement' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'isPrimary' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'isForeign' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'isUnique' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'isIndex' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'isCols' => $this->smallInteger(20)->defaultValue(0)->notNull(),
            'isRows' => $this->smallInteger(20)->defaultValue(0)->notNull(),
            'extra' => $this->text()->null(),
            'isSuccess' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'errorSummary' => $this->text()->null(),
            'status' => $this->smallInteger(1)->defaultValue(0)->notNull()->comment('0=TableMetaQueue, 1=TableMetaCompleted, 2=SchemaQueue, 3=DataQueue, 9=Processed'),
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
