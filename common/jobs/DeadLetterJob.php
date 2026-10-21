<?php

declare(strict_types=1);

namespace common\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;

class DeadLetterJob extends BaseObject implements JobInterface
{
    public string $jobClass = '';
    public string $payload = '';
    public string $error = '';
    public int $attempts = 0;

    public function execute($queue): void
    {
    }
}