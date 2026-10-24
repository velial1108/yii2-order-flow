<?php

declare(strict_types=1);

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Воркер для очереди уведомлений.
 */
class QueueNotificationsController extends Controller
{
    public function actionListen(int $timeout = 3): int
    {
        /** @var \yii\queue\amqp_interop\Queue $queue */
        $queue = Yii::$app->get('queueNotifications');
        $queue->listen();
        return ExitCode::OK;
    }
}