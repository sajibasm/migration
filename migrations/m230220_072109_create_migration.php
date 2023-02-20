<?php

use yii\db\Migration;

/**
 * Class m230220_072109_create_migration
 */
class m230220_072109_create_migration extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        echo "m230220_072109_create_migration began....\n";
        $this->createTable('tableCompare', [
            'id' => $this->primaryKey(),
            'tableName' => $this->string(100),
            'isEngine' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'engineType' => $this->string(10)->null(),
            'autoIncrement' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'autoIncrementKey' => $this->string(20)->null(),
            'isPrimary' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'primaryKeys' => $this->text()->null(),
            'isUnique' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'uniqueKeys' => $this->text()->null(),
            'isIndex' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'indexKeys' => $this->text()->null(),
            'maxColType' => $this->string(20),
            'maxColValue' => $this->string(50)->null(),
            'cols' => $this->integer(20)->defaultValue(0),
            'rows' => $this->integer(50)->defaultValue(0),
            'columnStatics' => $this->text()->null(),
            'isError' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'errorSummary' => $this->text()->null(),
            'status' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'createdAt' => $this->timestamp()->defaultValue(['expression'=>'CURRENT_TIMESTAMP']),
            'processedAt' => $this->timestamp()->defaultValue(['expression'=>'CURRENT_TIMESTAMP'])
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('tableCompare');
        echo "m230220_072109_create_migration cannot be reverted.\n";
        return false;
    }
}
