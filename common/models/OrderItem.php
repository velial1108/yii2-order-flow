<?php
declare(strict_types=1);
namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
/**
 * @property int $id
 * @property int $order_id
 * @property string $product_name
 * @property string $price
 * @property int $quantity
 */
class OrderItem extends ActiveRecord
{
    public const SCENARIO_CREATE_FROM_FORM = 'create-from-form';
    public static function tableName(): string
    {
     return '{{%order_items}}';
    }
    public function rules(): array
    {
        return [
            [['order_id', 'product_name', 'price'], 'required'],
            [['order_id', 'quantity'], 'integer'],
            [['price'], 'number', 'min' => 0],
            [['product_name'], 'string', 'max' => 255],
            [['quantity'], 'integer', 'min' => 1],
        ];
    }
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'order_id' => 'Заказ',
            'product_name' => 'Название товара',
            'price' => 'Цена',
            'quantity' => 'Количество',
        ];
    }
    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);

    }
    public function getSubtotal(): float
    {
        return (float)$this->price * (int)$this->quantity;
    }
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE_FROM_FORM] = [
            'product_name', 'price', 'quantity',
        ];
        return $scenarios;
    }
}