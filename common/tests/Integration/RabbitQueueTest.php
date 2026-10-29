<?php

declare(strict_types=1);

namespace common\tests\Integration;

use common\jobs\ProcessOrderJob;
use common\models\Order;
use common\tests\Support\IntegrationTester;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use common\jobs\OrderNotFoundException;
use Yii;
use yii\queue\Queue;

class RabbitQueueTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected function _before(): void
    {
        $this->ensureDeadLetterPolicy();
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'user', 'password');
        $channel = $connection->channel();

        // Удаляем тестовую очередь, если она осталась с другими аргументами.
        // Если очереди нет, RabbitMQ не вернёт ошибку.
        $channel->queue_delete('orders_test');

        // Объявляем без аргументов, как драйвер Yii2
        $channel->queue_declare('orders_test', false, true, false, false);

        $channel->exchange_declare('orders_test_exchange', 'direct', false, true, false);
        $channel->queue_bind('orders_test', 'orders_test_exchange', 'orders_test_routing');

        $channel->exchange_declare('orders_test_dead_exchange', 'direct', false, true, false);
        $channel->queue_declare('orders_test_dead', false, true, false, false);
        $channel->queue_purge('orders_test_dead');
        $channel->queue_bind('orders_test_dead', 'orders_test_dead_exchange', 'orders_test_dead');

        $channel->close();
        $connection->close();

        Order::deleteAll();
    }

    public function testJobIsPushedAndProcessed(): void
    {
        // Создаём заказ в БД (статус pending)
        $order = new Order();
        $order->user_id = 1;
        $order->status = Order::STATUS_PENDING;
        $order->total = '100.00';
        $this->assertTrue($order->save(), 'Order must be saved: ' . json_encode($order->getErrors()));

        /** @var Queue $queue */
        $queue = Yii::$app->get('queue');
        $queue->push(new ProcessOrderJob((int)$order->id));

        $this->tester->seeNumberOfMessagesInQueue('orders_test', 1);

        $context = $queue->getContext();
        $consumer = $context->createConsumer($context->createQueue('orders_test'));

        $message = $consumer->receive(2000); // таймаут 2 сек
        $this->assertNotNull($message, 'Message must be received from orders_test');

        $job = $queue->serializer->unserialize($message->getBody());
        $job->execute($queue);

        $consumer->acknowledge($message);
        $order->refresh();
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);

        $this->tester->seeQueueIsEmpty('orders_test');
    }
    private function ensureDeadLetterPolicy(): void
    {
        $body = json_encode([
            'pattern' => '^orders_test$',
            'definition' => [
                'dead-letter-exchange' => 'orders_test_dead_exchange',
                'dead-letter-routing-key' => 'orders_test_dead',
            ],
            'apply-to' => 'queues',
        ]);

        $context = stream_context_create(['http' => [
            'method' => 'PUT',
            'header' => "Content-Type: application/json\r\n"
                . 'Authorization: Basic ' . base64_encode('user:password') . "\r\n",
            'content' => $body,
            'ignore_errors' => true,
        ]]);

        file_get_contents('http://rabbitmq:15672/api/policies/%2F/test-dlx', false, $context);

        $status = (int)explode(' ', $http_response_header[0] ?? '')[1];
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("Не удалось создать политику test-dlx, HTTP $status");
        }
    }
    public function testMissingOrderGoesToDeadLetterQueue(): void
    {
        /** @var Queue $queue */
        $queue = Yii::$app->get('queue');

        // Заказа с таким id нет в БД
        $queue->push(new ProcessOrderJob(999999));
        $this->tester->seeNumberOfMessagesInQueue('orders_test', 1);

        $context = $queue->getContext();
        $consumer = $context->createConsumer($context->createQueue('orders_test'));
        $message = $consumer->receive(2000);
        $this->assertNotNull($message, 'Message must be received from orders_test');

        $job = $queue->serializer->unserialize($message->getBody());

        try {
            $job->execute($queue);
            $this->fail('Ожидалось исключение OrderNotFoundException');
        } catch (OrderNotFoundException $e) {
            // Имитация отказа воркера без возврата в очередь
            $consumer->reject($message, false);
        }

        $this->tester->seeNumberOfMessagesInQueue('orders_test_dead', 1);
        $this->tester->seeQueueIsEmpty('orders_test');
    }
}