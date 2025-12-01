<?php

declare(strict_types=1);

namespace App\Domain\Shared\Observers;

use App\Domain\Shared\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Audit Observer
 *
 * Automatically logs all changes to models that use the Auditable trait.
 * Tracks created, updated, and deleted events with full change history.
 *
 * Register this observer for critical models in EventServiceProvider:
 *   User::observe(AuditObserver::class);
 *   Order::observe(AuditObserver::class);
 *   Payment::observe(AuditObserver::class);
 */
final class AuditObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        $this->logAudit($model, AuditLog::EVENT_CREATED);
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        // Only log if there are actual changes
        if (!$model->wasChanged()) {
            return;
        }

        $this->logAudit($model, AuditLog::EVENT_UPDATED);
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->logAudit($model, AuditLog::EVENT_DELETED);
    }

    /**
     * Create an audit log entry.
     */
    private function logAudit(Model $model, string $event): void
    {
        // Check if model has Auditable trait and auditing is enabled
        if (!$this->isAuditingEnabled($model)) {
            return;
        }

        $oldValues = null;
        $newValues = null;

        switch ($event) {
            case AuditLog::EVENT_CREATED:
                $newValues = $this->getFilteredAttributes($model, $model->getAttributes());
                break;

            case AuditLog::EVENT_UPDATED:
                $oldValues = $this->getFilteredAttributes($model, $model->getOriginal());
                $newValues = $this->getFilteredAttributes($model, $model->getAttributes());
                // Only keep the changed attributes
                $changedKeys = array_keys($model->getChanges());
                $oldValues = array_intersect_key($oldValues, array_flip($changedKeys));
                $newValues = array_intersect_key($newValues, array_flip($changedKeys));
                break;

            case AuditLog::EVENT_DELETED:
                $oldValues = $this->getFilteredAttributes($model, $model->getOriginal());
                break;
        }

        AuditLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'user_id' => Auth::id(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'created_at' => now(),
        ]);
    }

    /**
     * Check if auditing is enabled for the model.
     */
    private function isAuditingEnabled(Model $model): bool
    {
        // Check if model uses Auditable trait
        if (method_exists($model, 'isAuditingEnabled')) {
            return $model->isAuditingEnabled();
        }

        return true;
    }

    /**
     * Get filtered attributes for audit logging.
     *
     * @param Model $model
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function getFilteredAttributes(Model $model, array $attributes): array
    {
        // If model has Auditable trait, use its filter method
        if (method_exists($model, 'filterAuditAttributes')) {
            return $model->filterAuditAttributes($attributes);
        }

        // Default: exclude common sensitive fields
        $exclude = ['password', 'remember_token', 'two_factor_secret'];

        return array_diff_key($attributes, array_flip($exclude));
    }
}
