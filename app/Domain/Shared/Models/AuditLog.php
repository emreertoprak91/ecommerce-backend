<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit Log Model
 *
 * Tracks all changes to critical models (User, Order, Payment, Product).
 *
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property int|null $user_id
 * @property string $event
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property \Carbon\Carbon $created_at
 */
final class AuditLog extends Model
{
    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'model_type', 'model_id');
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the changed attributes.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getChangedAttributesAttribute(): array
    {
        $changes = [];

        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];

        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Scope: Filter by model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope: Filter by model instance.
     */
    public function scopeForModelInstance($query, Model $model)
    {
        return $query->where('model_type', get_class($model))
                     ->where('model_id', $model->getKey());
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by event type.
     */
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
