<?php

declare(strict_types=1);

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_APP_BASE_PATH') or define('YII_APP_BASE_PATH', __DIR__ . '/../../');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../config/bootstrap.php';

// ПРИНУДИТЕЛЬНАЯ ИНИЦИАЛИЗАЦИЯ (обходит баг с чтением configFile в Codeception)
if (Yii::$app === null) {
    try {
        $config = require __DIR__ . '/../config/codeception-local.php';

        // Жесткая гарантия наличия id и basePath
        $config['id'] = $config['id'] ?? 'app-common-tests';
        $config['basePath'] = $config['basePath'] ?? dirname(__DIR__);

        Yii::$app = new yii\console\Application($config);
    } catch (\Throwable $e) {
        // Если приложение уже создано (InvalidCallException) — игнорируем.
        if (!Yii::$app) {
            throw $e; // Пробрасываем реальные ошибки (например, синтаксис в конфиге)
        }
    }
}