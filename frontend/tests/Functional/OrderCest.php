<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\Order;
use common\models\OrderItem;
use frontend\tests\Support\FunctionalTester;

class OrderCest
{
    public function _before(FunctionalTester $I): void
    {
        \Yii::$app->db->createCommand(
            'TRUNCATE orders, order_items RESTART IDENTITY CASCADE'
        )->execute();
    }

    public function tryToListEmptyOrders(FunctionalTester $I): void
    {
        $I->sendGET('/api/orders');

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([]);
    }

    public function tryToCreateOrder(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/api/orders', [
            'user_id' => 1,
            'items' => [
                ['product_name' => 'Book', 'price' => '500.00', 'quantity' => 2],
                ['product_name' => 'Pen', 'price' => '50.00', 'quantity' => 3],
            ],
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'success' => true,
            'order' => [
                'user_id' => 1,
                'status' => Order::STATUS_PENDING,
                'total' => '1150.00',
            ],
        ]);
    }

    public function tryToViewOrder(FunctionalTester $I): void
    {
        $order = new Order();
        $order->user_id = 1;
        $order->status = Order::STATUS_PENDING;
        $order->total = '1000.00';
        $order->save(false);

        $item = new OrderItem();
        $item->order_id = $order->id;
        $item->product_name = 'Book';
        $item->price = '500.00';
        $item->quantity = 2;
        $item->save(false);

        $I->sendGET("/api/orders/{$order->id}");

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => $order->id,
            'user_id' => 1,
            'status' => Order::STATUS_PENDING,
            'total' => '1000.00',
            'items' => [
                ['product_name' => 'Book', 'price' => '500.00', 'quantity' => 2, 'subtotal' => 1000],
            ],
        ]);
    }

    public function tryToViewNonExistentOrder(FunctionalTester $I): void
    {
        $I->sendGET('/api/orders/999999');

        $I->seeResponseCodeIs(404);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'name' => 'Not Found',
            'status' => 404,
        ]);
    }

    public function tryToCreateOrderWithoutUserId(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/api/orders', [
            'items' => [
                ['product_name' => 'Book', 'price' => '500.00', 'quantity' => 2],
            ],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['success' => false]);
        $I->seeResponseContains('user_id');
    }

    public function tryToCreateOrderWithoutItems(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/api/orders', [
            'user_id' => 1,
            'items' => [],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['success' => false]);
        $I->seeResponseContains('items');
    }

    public function tryToCreateOrderWithInvalidItem(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/api/orders', [
            'user_id' => 1,
            'items' => [
                ['product_name' => '', 'price' => '500.00', 'quantity' => 2],
            ],
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['success' => false]);
    }
}