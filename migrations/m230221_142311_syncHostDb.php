<?php

use yii\db\Migration;

/**
 * Class m230221_142311_syncHostDb
 */
class m230221_142311_syncHostDb extends Migration
{

    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('sync_host_db', [
            'id' => $this->primaryKey(),
            'host' => $this->string(100)->notNull(),
            'dbname' => $this->string(100)->null(),
            'type' => $this->smallInteger(1)->defaultValue(1)->comment('1=Target, 2=Destination'),
            'createdAt' => $this->timestamp()->defaultExpression('NOW()')->notNull(),
            'updatedAt' => $this->timestamp()->defaultExpression('NOW()')->notNull()
        ],  $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230221_142311_syncHostDb cannot be reverted.\n";
        $this->dropTable('sync_host_db');
        return false;
    }

}
