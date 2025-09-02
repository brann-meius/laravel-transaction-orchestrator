<?php

declare(strict_types=1);

namespace Meius\LaravelTransactionOrchestrator\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $price
 * @property int $stock_quantity
 */
class Product extends Model
{
    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }
}
