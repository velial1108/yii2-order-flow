<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%orders}}`.
 */
class m261008_124329_create_orders_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%orders}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('pending'),
            'total' => $this->decimal(12,2)->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->notNull()->defaultExpression(new \yii\db\Expression('CURRENT_TIMESTAMP')),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression(new \yii\db\Expression('CURRENT_TIMESTAMP')),
        ]);
        $this->createIndex('idx-orders-user_id', '{{%orders}}', 'user_id');
        $this->createIndex('idx-orders-status', '{{%orders}}', 'status');
        $this->createIndex('idx-orders-created_at', '{{%orders}}', 'created_at');

        $this->createTable('{{%order_items}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_name' => $this->string(255)->notNull(),
            'price' => $this->decimal(12,2)->notNull()->defaultValue(0),
            'quantity' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->createIndex('idx-order_items-order_id', '{{%order_items}}', 'order_id');

        $this->addForeignKey(
            'fk-order_items-order_id',
            '{{%order_items}}',
            'order_id',
            '{{%orders}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-order_items-order_id', '{{%order_items}}');
        $this->dropTable('{{%order_items}}');
        $this->dropTable('{{%orders}}');
    }
}
