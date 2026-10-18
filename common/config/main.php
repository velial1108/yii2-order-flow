<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$paramsLocal = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];
$params = array_merge($params, $paramsLocal);

return [
    'bootstrap' => [
        \common\bootstrap\MailerBootstrap::class,
        'queue', // ← добавляем: регистрирует консольные команды queue/*
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'queue' => [
            'class' => \yii\queue\amqp_interop\Queue::class,
            'host' => $params['rabbitmq']['host'],
            'port' => (int) $params['rabbitmq']['port'],
            'user' => $params['rabbitmq']['user'],
            'password' => $params['rabbitmq']['password'],
            'queueName' => $params['rabbitmq']['queueName'],
            'driver' => \yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_LIB,
            'as log' => \yii\queue\LogBehavior::class,
        ],
    ],
    'params' => $params,
];