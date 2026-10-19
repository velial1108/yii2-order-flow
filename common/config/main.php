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
        'queue',
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
    ],
    'params' => $params,
];
