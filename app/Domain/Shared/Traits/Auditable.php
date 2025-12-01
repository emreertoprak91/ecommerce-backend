<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

/**
 * Auditable Trait
 *
 * Add this trait to any model that needs audit logging.
 * Works in conjunction with AuditObserver.
 */
trait Auditable
{
    /**
     * Get the attributes that should be excluded from audit logs.
     *
     * Override this method in your model to exclude sensitive attributes.
     *
     * @return array<string>
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? ['password', 'remember_token', 'two_factor_secret'];
    }

    /**
     * Get the attributes that should be included in audit logs.
     *
     * If empty, all attributes (except excluded) will be logged.
     *
     * @return array<string>
     */
    public function getAuditInclude(): array
    {
        return $this->auditInclude ?? [];
    }

    /**
     * Determine if auditing is enabled for this model.
     */
    public function isAuditingEnabled(): bool
    {
        return $this->auditEnabled ?? true;
    }

    /**
     * Get filtered attributes for audit log.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function filterAuditAttributes(array $attributes): array
    {
        $exclude = $this->getAuditExclude();
        $include = $this->getAuditInclude();

        // If include is specified, only include those attributes
        if (!empty($include)) {
            $attributes = array_intersect_key($attributes, array_flip($include));
        }

        // Always exclude sensitive attributes
        return array_diff_key($attributes, array_flip($exclude));
    }

    /**
     * Get the audit log entries for this model.
     */
    public function auditLogs()
    {
        return $this->morphMany(\App\Domain\Shared\Models\AuditLog::class, 'auditable', 'model_type', 'model_id');
    }
}
