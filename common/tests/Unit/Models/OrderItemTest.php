<?php

declare(strict_types=1);

namespace common\tests\Unit\Models;

use common\models\OrderItem;
use common\tests\Support\UnitTester;

class OrderItemTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function testSubtotalCalculation(): void
    {
        $item = new OrderItem();
        $item->price = '500.00';
        $item->quantity = 2;

        $this->assertSame(1000.0, $item->getSubtotal());
    }

    public function testSubtotalWithDecimalPrices(): void
    {
        $item = new OrderItem();
        $item->price = '99.99';
        $item->quantity = 3;

        $this->assertEqualsWithDelta(299.97, $item->getSubtotal(), 0.001);
    }

    public function testValidationRequiresProductName(): void
    {
        $item = new OrderItem();
        $item->order_id = 1;
        $item->price = '100.00';
        $item->quantity = 1;
        // product_name пустой

        $this->assertFalse($item->validate());
        $this->assertArrayHasKey('product_name', $item->getErrors());
    }

    public function testValidationRejectsNegativePrice(): void
    {
        $item = new OrderItem();
        $item->order_id = 1;
        $item->product_name = 'Test';
        $item->price = '-10.00';
        $item->quantity = 1;

        $this->assertFalse($item->validate());
        $this->assertArrayHasKey('price', $item->getErrors());
    }

    public function testValidationRejectsZeroQuantity(): void
    {
        $item = new OrderItem();
        $item->order_id = 1;
        $item->product_name = 'Test';
        $item->price = '100.00';
        $item->quantity = 0;

        $this->assertFalse($item->validate());
        $this->assertArrayHasKey('quantity', $item->getErrors());
    }

    public function testScenarioCreateFromFormSkipsOrderId(): void
    {
        $item = new OrderItem();
        $item->setScenario(OrderItem::SCENARIO_CREATE_FROM_FORM);
        $item->product_name = 'Test';
        $item->price = '100.00';
        $item->quantity = 1;
        // order_id не задан — но в этом сценарии не валидируется

        $this->assertTrue($item->validate());
    }
}