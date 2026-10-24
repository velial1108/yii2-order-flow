<?php

declare(strict_types=1);

namespace console\controllers;

use OpenApi\Generator;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class SwaggerController extends Controller
{
    public function actionGenerate(): int
    {
        $source = Yii::getAlias('@frontend/modules/api');
        $output = Yii::getAlias('@frontend/web/swagger.json');

        $openapi = (new Generator())->generate([$source]);

        if ($openapi === null) {
            $this->stderr("Failed to generate OpenAPI spec.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (file_put_contents($output, $openapi->toJson()) === false) {
            $this->stderr("Cannot write {$output}\n", Console::FG_RED);
            return ExitCode::CANTCREAT;
        }

        $this->stdout("Swagger JSON generated: {$output}\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}