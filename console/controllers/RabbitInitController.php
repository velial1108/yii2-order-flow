<?php

declare(strict_types=1);

namespace console\controllers;

use Enqueue\AmqpLib\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind; // 👈 Импортируем реализацию
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class RabbitInitController extends Controller
{
    public function actionIndex(): int
    {
        /** @var \yii\queue\amqp_interop\Queue $queue */
        $queue = Yii::$app->get('queue');

        /** @var AmqpContext $context */
        $context = $queue->getContext();

        // 1. Заказы
        $ordersDlx = $context->createTopic('orders_dlx');
        $ordersDlx->setType(AmqpTopic::TYPE_DIRECT);
        $ordersDlx->addFlag(AmqpDestination::FLAG_DURABLE);
        $context->declareTopic($ordersDlx);
        $this->stdout("Exchange 'orders_dlx' created.\n");

        $ordersDead = $context->createQueue('orders_dead');
        $ordersDead->addFlag(AmqpDestination::FLAG_DURABLE);
        $context->declareQueue($ordersDead);
        $this->stdout("Queue 'orders_dead' created.\n");

        $context->bind(new AmqpBind($ordersDead, $ordersDlx, 'orders_dead'));
        $this->stdout("Binding 'orders_dlx' -> 'orders_dead' created.\n");

        // 2. Уведомления
        $notificationsDlx = $context->createTopic('notifications_dlx');
        $notificationsDlx->setType(AmqpTopic::TYPE_DIRECT);
        $notificationsDlx->addFlag(AmqpDestination::FLAG_DURABLE);
        $context->declareTopic($notificationsDlx);
        $this->stdout("Exchange 'notifications_dlx' created.\n");

        $notificationDead = $context->createQueue('notifications_dead');
        $notificationDead->addFlag(AmqpDestination::FLAG_DURABLE);
        $context->declareQueue($notificationDead);
        $this->stdout("Queue 'notifications_dead' created.\n");

        $context->bind(new AmqpBind($notificationDead, $notificationsDlx, 'notifications_dead'));
        $this->stdout("Binding 'notifications_dlx' -> 'notifications_dead' created.\n");

        return ExitCode::OK;
    }
}