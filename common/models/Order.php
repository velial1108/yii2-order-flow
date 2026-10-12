<?php
declare(strict_types=1);
namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $total
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read OrderItem[] $items
 */
class Order extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public static function tableName(): string
    {
        return '{{%orders}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer', 'min' => 1],
            [['status'], 'string', 'max' => 32],
            [['status'], 'in', 'range' => array_keys(self::getStatuses())],
            [['total'], 'number', 'min' => 0],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'status' => 'Статус',
            'total' => 'Сумма',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getItems(): ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидает обработки',
            self::STATUS_PROCESSING => 'В обработке',
            self::STATUS_COMPLETED => 'Завершён',
            self::STATUS_FAILED => 'Ошибка',
        ];
    }

    /**
     * Пересчитать total из позиций.
     */
    public function recalculateTotal(): void
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getSubtotal();
        }
        $this->total = number_format($total, 2, '.', '');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = [
            self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_FAILED],
            self::STATUS_PROCESSING => [self::STATUS_COMPLETED, self::STATUS_FAILED],
            self::STATUS_COMPLETED => [],
            self::STATUS_FAILED => [self::STATUS_PENDING],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? [], true);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}