<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use App\Domain\Shared\Traits\Auditable;
use App\Models\User;
use Database\Factories\Domain\Order\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $order_number
 * @property string $status
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $shipping_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property string $currency
 * @property string|null $shipping_name
 * @property string|null $shipping_phone
 * @property string|null $shipping_address
 * @property string|null $shipping_city
 * @property string|null $shipping_district
 * @property string|null $shipping_postal_code
 * @property string $shipping_country
 * @property string|null $billing_name
 * @property string|null $billing_phone
 * @property string|null $billing_address
 * @property string|null $billing_city
 * @property string|null $billing_district
 * @property string|null $billing_postal_code
 * @property string $billing_country
 * @property string|null $payment_method
 * @property string $payment_status
 * @property \Carbon\Carbon|null $paid_at
 * @property string|null $notes
 * @property string|null $admin_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;
    use SoftDeletes;
    use Auditable;

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_district',
        'shipping_postal_code',
        'shipping_country',
        'billing_name',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_district',
        'billing_postal_code',
        'billing_country',
        'payment_method',
        'payment_status',
        'paid_at',
        'notes',
        'admin_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(\App\Domain\Payment\Models\Payment::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'completed' || $this->status === self::STATUS_PAID;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function getFormattedTotalAttribute(): string
    {
        $symbol = $this->currency === 'TRY' ? '₺' : $this->currency;
        return $symbol . number_format((float) $this->total_amount, 2, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_PROCESSING => 'İşleniyor',
            self::STATUS_PAID => 'Ödendi',
            self::STATUS_SHIPPED => 'Kargoya Verildi',
            self::STATUS_DELIVERED => 'Teslim Edildi',
            self::STATUS_CANCELLED => 'İptal Edildi',
            self::STATUS_REFUNDED => 'İade Edildi',
            default => $this->status,
        };
    }
}
