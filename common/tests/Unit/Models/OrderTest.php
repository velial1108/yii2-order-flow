<?php

declare(strict_types=1);

namespace common\tests\Unit\Models;

use common\models\Order;
use common\tests\Support\UnitTester;

class OrderTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function testStatusConstantsExist(): void
    {
        $this->assertSame('pending', Order::STATUS_PENDING);
        $this->assertSame('processing', Order::STATUS_PROCESSING);
        $this->assertSame('completed', Order::STATUS_COMPLETED);
        $this->assertSame('failed', Order::STATUS_FAILED);
    }

    public function testGetStatusesReturnsMap(): void
    {
        $statuses = Order::getStatuses();

        $this->assertIsArray($statuses);
        $this->assertArrayHasKey(Order::STATUS_PENDING, $statuses);
        $this->assertCount(4, $statuses);
    }

    public function testCanTransitionToValidTransitions(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_PENDING;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PROCESSING));
        $this->assertTrue($order->canTransitionTo(Order::STATUS_FAILED));
    }

    public function testCanTransitionToInvalidTransitions(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_PENDING;

        $this->assertFalse($order->canTransitionTo(Order::STATUS_COMPLETED));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_PENDING));
    }

    public function testCompletedIsTerminal(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_COMPLETED;

        $this->assertFalse($order->canTransitionTo(Order::STATUS_PENDING));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_PROCESSING));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_FAILED));
    }

    public function testIsPendingAndIsCompleted(): void
    {
        $order = new Order();

        $order->status = Order::STATUS_PENDING;
        $this->assertTrue($order->isPending());
        $this->assertFalse($order->isCompleted());

        $order->status = Order::STATUS_COMPLETED;
        $this->assertFalse($order->isPending());
        $this->assertTrue($order->isCompleted());
    }
}