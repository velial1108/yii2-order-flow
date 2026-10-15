<?php

declare(strict_types=1);

namespace frontend\modules\api;

use Yii;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public $controllerNamespace = 'frontend\modules\api\controllers';
    public function init(): void
    {
        parent::init();
        Yii::$app->request->enableCsrfValidation = false;
        Yii::$app->user->enableSession = false;
        Yii::$app->user->enableAutoLogin = false;
        Yii::$app->request->parsers['application/json'] = \yii\web\JsonParser::class;
    }
}