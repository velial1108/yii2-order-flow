<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\Order;
use common\models\OrderItem;
use yii\console\Controller;
use yii\console\ExitCode;

class SandboxController extends Controller
{
    public function actionOrder(): int
    {
        // Создаём заказ
        $order = new Order();
        $order->user_id = 1;
        $order->status = Order::STATUS_PENDING;
        $order->total = 0;

        if (!$order->save()) {
            echo "Ошибка создания заказа:\n";
            print_r($order->getErrors());
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $order->refresh();

        echo "Заказ #{$order->id} создан. created_at: {$order->created_at}\n";

        // Добавляем позиции
        $item1 = new OrderItem();
        $item1->order_id = $order->id;
        $item1->product_name = 'Книга';
        $item1->price = '500.00';
        $item1->quantity = 2;
        $item1->save();

        $item2 = new OrderItem();
        $item2->order_id = $order->id;
        $item2->product_name = 'Ручка';
        $item2->price = '50.00';
        $item2->quantity = 3;
        $item2->save();

        // Пересчитываем total
        $order->recalculateTotal();
        $order->save();

        echo "Total пересчитан: {$order->total}\n";

        // Проверяем связь
        $items = $order->items;
        echo "Позиций в заказе: " . count($items) . "\n";
        foreach ($items as $item) {
            echo "  - {$item->product_name} x{$item->quantity} = {$item->getSubtotal()}\n";
        }

        // Проверяем переходы
        echo "\nПереходы статусов:\n";
        echo "  pending → processing: " . ($order->canTransitionTo(Order::STATUS_PROCESSING) ? 'да' : 'нет') . "\n";
        echo "  pending → completed: " . ($order->canTransitionTo(Order::STATUS_COMPLETED) ? 'да' : 'нет') . "\n";

        return ExitCode::OK;
    }
    public function actionCreateOrder(): int
    {
        $form = new \common\models\forms\CreateOrderForm();
        $form->user_id = 1;
        $form->items = [
            ['product_name' => 'Книга', 'price' => '500.00', 'quantity' => 2],
            ['product_name' => 'Ручка', 'price' => '50.00', 'quantity' => 3],
        ];

        $order = $form->create();

        if ($order === null) {
            echo "Ошибка:\n";
            print_r($form->getErrors());
            return ExitCode::UNSPECIFIED_ERROR;
        }

        echo "Заказ #{$order->id} создан, total = {$order->total}\n";
        echo "Позиций: " . count($order->items) . "\n";

        return ExitCode::OK;
    }
}