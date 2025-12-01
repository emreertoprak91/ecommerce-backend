<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use App\Domain\Shared\Traits\Auditable;
use Database\Factories\Domain\Product\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category Model
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $sort_order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;
    use Auditable;

    protected $table = 'categories';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the products for the category.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product')
            ->withTimestamps();
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root categories.
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
