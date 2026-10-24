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

    public function execute($queue = null): void
    {
        //тест DLQ
//        throw new \RuntimeException('тестовая ошибка для DLQ');
        /** @var \yii\mutex\Mutex $mutex */
        $mutex = Yii::$app->get('mutex');
        $lockName = 'order_' . $this->orderId;

        // попытка блокировки ждем 10 сек
        if (!$mutex->acquire($lockName, 10)) {
            Yii::info("Заказ #{$this->orderId} уже обрабатывается другим воркером. Пропускаем.", 'queue');
            return;
        }

        try {
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
            }

            sleep(3); // Имитация работы

            if ($order->canTransitionTo(Order::STATUS_COMPLETED)) {
                $order->status = Order::STATUS_COMPLETED;
                $order->save(false);
                Yii::$app->cache->delete("order_{$this->orderId}");
                Yii::info("Заказ #{$this->orderId} успешно завершен", 'queue');
            }
        } finally {
            $mutex->release($lockName);
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
