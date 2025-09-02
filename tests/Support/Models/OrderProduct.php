<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Support\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 */
class OrderProduct extends Pivot
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
