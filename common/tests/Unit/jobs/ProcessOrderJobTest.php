<?php

namespace common\tests\Unit\jobs;

use Codeception\Test\Unit;
use common\jobs\ProcessOrderJob;
use common\models\Order;
use common\tests\Support\UnitTester;

class ProcessOrderJobTest extends Unit
{
    protected UnitTester $tester;

    protected function _before(): void
    {
        Order::deleteAll();
    }

    public function testExecuteSuccessfully()
    {
        $order = new Order();
        $order->user_id = 1;
        $order->status = Order::STATUS_PENDING;
        $order->total = (string)100;
        $order->save(false);

        $job = new ProcessOrderJob((int)$order->id);
        $job->execute(null);
        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);
    }

    public function testExecuteThrowsExceptionIfOrderNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Order #999999 not found');
        $job = new ProcessOrderJob(999999);
        $job->execute(null);
    }

    public function testExecuteSkipsIfAlreadyCompleted(): void
    {
        $order = new Order();
        $order->user_id = 1;
        $order->status = Order::STATUS_COMPLETED;
        $order->total = (string)100;
        $order->save(false);

        $job = new ProcessOrderJob((int)$order->id);
        $job->execute(null);

        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);
    }
}
