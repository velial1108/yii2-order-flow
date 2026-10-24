<?php
declare(strict_types=1);
namespace common\models\forms;

use common\models\Order;
use common\models\OrderItem;
use yii\base\Model;
use yii\db\Exception as DbException;

class CreateOrderForm extends Model
{
    public int|string|null $user_id = null;
    public array $items = [];

    public function rules(): array
    {
        return [
            [['user_id' , 'items'], 'required'],
            [['user_id'], 'integer', 'min' => 1],
            [['items'], 'validateItems'],
            [['items'], 'validateItemsCount'],
        ];
    }
    public function attributeLabels(): array
    {
        return [
            'user_id' => 'Пользователь',
            'items' => 'Позиция заказа',
        ];
    }
    public function validateItems(string $attribute): void
    {
        foreach ($this->items as $index => $itemData) {
            if (!is_array($itemData)) {
                $this->addError($attribute, "Позиция #{$index}: должна быть объектом");
                continue;
            }

            $item = new OrderItem();
            $item->setScenario(OrderItem::SCENARIO_CREATE_FROM_FORM);
            $item->load($itemData, '');

            if (!$item->validate()) {
                foreach ($item->getErrors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $this->addError($attribute, "Позиция #{$index} ({$field}): {$error}");
                    }
                }
            }
        }
    }
    /**
     * Проверка, что items — не пустой массив.
     */
    public function validateItemsCount(string $attribute): void
    {
        if (count($this->items) === 0) {
            $this->addError($attribute, 'Заказ должен содержать минимум одну позицию');
        }
    }
    /**
     * Создать заказ с позициями в транзакции.
     */
    public function create(): ?Order
    {
        if (!$this->validate()) {
            return null;
        }

        $transaction = \Yii::$app->db->beginTransaction();

        try {
            $order = new Order();
            $order->user_id = (int)$this->user_id;
            $order->status = Order::STATUS_PENDING;
            $order->total = '0.00'; // было 0 (int) → string-поле

            if (!$order->save()) {
                throw new DbException('Не удалось создать заказ: ' . json_encode($order->getErrors()));
            }

            foreach ($this->items as $itemData) {
                $item = new OrderItem();
                $item->setScenario(OrderItem::SCENARIO_CREATE_FROM_FORM);
                $item->load($itemData, '');
                $item->order_id = $order->id;

                if (!$item->save()) {
                    throw new DbException('Не удалось создать позицию: ' . json_encode($item->getErrors()));
                }
            }

            $order->recalculateTotal();
            $order->save();

            $transaction->commit();
            return $order;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->addError('items', 'Ошибка создания заказа: ' . $e->getMessage());
            return null;
        }
    }
}