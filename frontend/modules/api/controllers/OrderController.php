<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use common\models\forms\CreateOrderForm;
use common\models\Order;
use Yii;
use yii\data\ActiveDataProvider;
use yii\queue\Queue;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class OrderController extends Controller
{
    public function actionIndex(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Order::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
    }

    public function actionView(int $id): array
    {
        $cacheKey = "order_{$id}";

        //вызов из кэша
        $data = Yii::$app->cache->get($cacheKey);
        if ($data !== false) {
            //нашли в кэше отдали
            return $data;
        }
        //пробуем брать в базе если кэш пуст
        $order = Order::findOne($id);
        if ($order === null) {
            throw new NotFoundHttpException("Заказ #{$id} не найден");
        }
        $data = $this->serializeOrder($order);

        //храним в кэше 300 сек
        Yii::$app->cache->set($cacheKey, $data, 300);
        return $data;
    }

    public function actionCreate(): array
    {
        $form = new CreateOrderForm();
        $form->load(Yii::$app->request->post(), '');

        $order = $form->create();
        Yii::$app->cache->delete("order_{$order->id}");
        if ($order === null) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'errors' => $form->getErrors(),
            ];
        }
        Yii::$app->queue->push(new \common\jobs\ProcessOrderJob((int)$order->id));
        Yii::$app->response->statusCode = 201;
        return [
            'success' => true,
            'order' => $this->serializeOrder($order),
        ];
    }
    public function actionTestEmail(): array
    {
        Yii::$app->queueNotifications->push(new \common\jobs\SendEmailJob([
            'email' => 'test@example.com',
            'subject' => 'Тест уведомления',
        ]));

        return ['success' => true, 'message' => 'Задача в очереди notifications'];
    }
    private function serializeOrder(Order $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->getSubtotal(),
            ];
        }

        return [
            'id' => $order->id,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'total' => $order->total,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'items' => $items,
        ];
    }
}
