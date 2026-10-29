<?php

namespace common\jobs;

use common\models\Order;
use Yii;
use yii\base\BaseObject;
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
        // throw new \RuntimeException('тестовая ошибка для DLQ');

        $mutex = Yii::$app->mutex;
        $lockName = 'order_' . $this->orderId;

        if (!$mutex->acquire($lockName, 10)) {
            Yii::warning("Заказ #{$this->orderId} уже обрабатывается другим воркером, пропускаем", 'queue');
            return;
        }

        $order = null;
        try {
            $order = Order::findOne($this->orderId);
            if ($order === null) {
                throw new OrderNotFoundException("Order #{$this->orderId} not found.");
            }

            // Финальный статус: повтор не нужен
            if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING], true)) {
                Yii::info("Заказ #{$this->orderId} в статусе {$order->status}, пропускаем", 'queue');
                return;
            }

            // Если упали в processing, продолжаем с того же места
            if ($order->status === Order::STATUS_PENDING) {
                $this->transition($order, Order::STATUS_PROCESSING);
            }

            sleep(3); // Имитация работы

            $this->transition($order, Order::STATUS_COMPLETED);
            Yii::$app->cache->delete("order_{$this->orderId}");
            Yii::info("Заказ #{$this->orderId} успешно завершен", 'queue');
        } catch (\Throwable $e) {
            Yii::error([
                'message' => 'ProcessOrderJob failed',
                'orderId' => $this->orderId,
                'status' => $order?->status,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ], 'queue');
            throw $e;
        } finally {
            $mutex->release($lockName);
        }
    }

    private function transition(Order $order, string $to): void
    {
        if (!$order->canTransitionTo($to)) {
            throw new \LogicException("Недопустимый переход {$order->status} -> {$to}");
        }
        $order->status = $to;
        if (!$order->save(false)) {
            throw new \RuntimeException("Не удалось сохранить заказ #{$order->id}");
        }
    }

    public function getTtr(): int
    {
        return 60;
    }

    public function canRetry($attempt, $error): bool
    {
        // Эти ошибки повтором не исправить: сразу в DLQ
        if ($error instanceof OrderNotFoundException || $error instanceof \LogicException) {
            return false;
        }
        return $attempt < 3;
    }
}