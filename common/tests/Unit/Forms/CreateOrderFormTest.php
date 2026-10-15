<?php

declare(strict_types=1);

namespace common\tests\Unit\Forms;

use common\models\forms\CreateOrderForm;
use common\models\Order;
use common\tests\Support\UnitTester;

class CreateOrderFormTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function testValidFormPassesValidation(): void
    {
        $form = new CreateOrderForm();
        $form->user_id = 1;
        $form->items = [
            ['product_name' => 'Book', 'price' => '500.00', 'quantity' => 2],
        ];

        $this->assertTrue($form->validate());
    }

    public function testRequiresUserId(): void
    {
        $form = new CreateOrderForm();
        $form->items = [
            ['product_name' => 'Book', 'price' => '500.00', 'quantity' => 2],
        ];

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('user_id', $form->getErrors());
    }

    public function testRequiresAtLeastOneItem(): void
    {
        $form = new CreateOrderForm();
        $form->user_id = 1;
        $form->items = [];

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('items', $form->getErrors());
    }

    public function testRejectsInvalidItem(): void
    {
        $form = new CreateOrderForm();
        $form->user_id = 1;
        $form->items = [
            ['product_name' => '', 'price' => '500.00', 'quantity' => 2],
        ];

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('items', $form->getErrors());
    }

    public function testRejectsNonArrayItem(): void
    {
        $form = new CreateOrderForm();
        $form->user_id = 1;
        $form->items = ['not-an-array'];

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('items', $form->getErrors());
    }

    public function testUserIdMustBePositive(): void
    {
        $form = new CreateOrderForm();
        $form->user_id = 0;
        $form->items = [
            ['product_name' => 'Book', 'price' => '500.00', 'quantity' => 2],
        ];

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('user_id', $form->getErrors());
    }
}