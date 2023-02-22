<?php

use yii\db\Migration;

/**
 * Class m230221_140811_config
 */
class m230221_140811_config extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('sync_config', [
            'id' => $this->primaryKey(),
            'dbType' => $this->smallInteger(1)->defaultValue(1)->comment('1=mysql,2=mssql,3=oracle'),
            'type' => $this->smallInteger(1)->defaultValue(1)->comment('1=Target, 2=Destination'),
            'host' => $this->string(100)->notNull(),
            'dbname' => $this->string(100)->null(),
            'username' => $this->string(50)->notNull(),
            'password' => $this->string(100)->notNull(),
            'charset' => $this->string(100)->defaultValue('utf8')->notNull(),
            'status' => $this->smallInteger(1)->defaultValue(0)->notNull()->comment('0=Inactive, 1=Active'),
            'createdAt' => $this->timestamp()->defaultExpression('NOW()')->notNull(),
            'updatedAt' => $this->timestamp()->defaultExpression('NOW()')->notNull()
        ],  $tableOptions);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230221_140811_config cannot be reverted.\n";
        $this->dropTable('sync_config');
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230221_140811_config cannot be reverted.\n";

        return false;
    }
    */
}
