<?php
declare(strict_types=1);

namespace common\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendEmailJob extends BaseObject implements JobInterface
{
    public string $email;
    public string $subject;

    public function execute($queue): void
    {
        echo ">>> [WORKER-NOTIFICATIONS] Отправка письма на {$this->email}...\n";
        sleep(2);
        echo ">>> [WORKER-NOTIFICATIONS] Письмо на {$this->email} отправлено!\n";
    }
}