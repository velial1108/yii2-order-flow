<?php

declare(strict_types=1);

namespace frontend\controllers;

use yii\web\Controller;

class ApiDocController extends Controller
{
    public function actionIndex(): string
    {
        return $this->renderPartial('index');
    }
}