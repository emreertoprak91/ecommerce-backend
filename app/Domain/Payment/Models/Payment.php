<?php

declare(strict_types=1);

namespace App\Domain\Payment\Models;

use App\Domain\Order\Models\Order;
use App\Domain\Shared\Traits\Auditable;
use App\Models\User;
use Database\Factories\Domain\Payment\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $order_id
 * @property string $merchant_oid
 * @property string $payment_provider
 * @property string|null $payment_method
 * @property float $amount
 * @property int $payment_amount
 * @property string $currency
 * @property string $status
 * @property string|null $paytr_token
 * @property string|null $transaction_id
 * @property string|null $masked_pan
 * @property string|null $installment_count
 * @property array|null $provider_response
 * @property string|null $error_message
 * @property bool $terms_accepted
 * @property \Carbon\Carbon|null $terms_accepted_at
 * @property string|null $terms_acceptance_ip
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;
    use SoftDeletes;
    use Auditable;

    /**
     * Attributes to exclude from audit logs (sensitive payment data).
     *
     * @var array<string>
     */
    protected array $auditExclude = [
        'paytr_token',
        'provider_response',
    ];

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    protected $fillable = [
        'user_id',
        'order_id',
        'merchant_oid',
        'payment_provider',
        'payment_method',
        'amount',
        'payment_amount',
        'currency',
        'status',
        'paytr_token',
        'transaction_id',
        'masked_pan',
        'installment_count',
        'provider_response',
        'error_message',
        'terms_accepted',
        'terms_accepted_at',
        'terms_acceptance_ip',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_amount' => 'integer',
        'provider_response' => 'array',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markAsCompleted(string $transactionId, ?array $response = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'transaction_id' => $transactionId,
            'provider_response' => $response,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage, ?array $response = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'provider_response' => $response,
        ]);
    }

    public function getFormattedAmountAttribute(): string
    {
        $symbol = $this->currency === 'TRY' ? '₺' : $this->currency;
        return $symbol . number_format((float) $this->amount, 2, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_PROCESSING => 'İşleniyor',
            self::STATUS_COMPLETED => 'Tamamlandı',
            self::STATUS_FAILED => 'Başarısız',
            self::STATUS_REFUNDED => 'İade Edildi',
            self::STATUS_CANCELLED => 'İptal Edildi',
            default => $this->status,
        };
    }
}
