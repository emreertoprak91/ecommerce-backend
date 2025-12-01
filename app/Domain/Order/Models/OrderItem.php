<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Shared\Traits\Auditable;
use Database\Factories\Domain\Order\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $product_name
 * @property string $product_sku
 * @property string|null $product_description
 * @property string|null $product_image
 * @property int $quantity
 * @property float $unit_price
 * @property float $total_price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;
    use Auditable;

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_description',
        'product_image',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return '₺' . number_format((float) $this->unit_price, 2, ',', '.');
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return '₺' . number_format((float) $this->total_price, 2, ',', '.');
    }
}
