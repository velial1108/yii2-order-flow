<?php

namespace common\jobs;

use common\models\Order;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidCallException;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

class ProcessOrderJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    public int $orderId;
    public function __construct(int $orderId, $config = [])
    {
        $this->orderId = $orderId;
        parent::__construct($config);
    }

    public function execute($queue): void
    {
        //тест DLQ
        throw new \RuntimeException('тестовая ошибка для DLQ');

        $order = Order::findOne($this->orderId);

        if ($order === null) {
            throw new \RuntimeException("Order #{$this->orderId} not found.");
        }
        if (!$order->isPending()) {
            Yii::info("Заказ #{$this->orderId} уже в статусе {$order->status}. Пропускаем.", 'queue');
            return;
        }
        if ($order->canTransitionTo(Order::STATUS_PROCESSING)) {
            $order->status = Order::STATUS_PROCESSING;
            $order->save(false);
            Yii::info("Заказ #{$this->orderId} взят в обработку", 'queue');
        }

        try {
            sleep(3);
            if ($order->canTransitionTo(Order::STATUS_COMPLETED)) {
                $order->status = Order::STATUS_COMPLETED;
                $order->save(false);
                Yii::info("Заказ #{$this->orderId} успешно завершен", 'queue');
            }
        } catch (\Throwable $e) {
            if ($order->canTransitionTo(Order::STATUS_FAILED)) {
                $order->status = Order::STATUS_FAILED;
                $order->save(false);
            }
            Yii::error("Ошибка обработки заказа #{$this->orderId}: " . $e->getMessage(), 'queue');

            throw $e;
        }
    }
    public function getTtr(): int
    {
        return 60;
    }
    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3;
    }
}
