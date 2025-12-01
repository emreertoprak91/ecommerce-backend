<?php

declare(strict_types=1);

namespace App\Domain\Product\Models;

use App\Domain\Shared\Traits\Auditable;
use Database\Factories\Domain\Product\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Product Model
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property string|null $description
 * @property int $price
 * @property int|null $compare_price
 * @property int|null $cost
 * @property int $quantity
 * @property bool $is_active
 * @property bool $is_featured
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use SoftDeletes;
    use Auditable;

    /**
     * The table associated with the model.
     */
    protected $table = 'products';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'compare_price',
        'cost',
        'quantity',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'metadata',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'integer',
        'compare_price' => 'integer',
        'cost' => 'integer',
        'quantity' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the categories for the product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withTimestamps();
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include published products.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to filter by price range.
     */
    public function scopePriceRange(Builder $query, ?int $min = null, ?int $max = null): Builder
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }

        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    /**
     * Scope a query to search products.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price / 100, 2, ',', '.') . ' TL';
    }

    /**
     * Check if product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Get discount percentage.
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return null;
        }

        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }
}
