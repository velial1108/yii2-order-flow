<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use yii\rest\Controller;

class HealthController extends Controller
{
    public function actionIndex(): array
    {
        return [
            'status' => 'ok',
            'time' => date(DATE_ATOM),
            'module' => 'api',
            'php' => PHP_VERSION,
        ];
    }
}