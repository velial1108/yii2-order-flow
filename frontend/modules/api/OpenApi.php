<?php

declare(strict_types=1);

namespace frontend\modules\api;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Yii2 Order Flow API', version: '1.0.0')]
#[OA\Server(url: '/', description: 'Current host')]
final class OpenApi
{
}