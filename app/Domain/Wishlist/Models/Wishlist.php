<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Shared\Traits\Auditable;
use App\Models\User;
use Database\Factories\Domain\Wishlist\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Wishlist extends Model
{
    /** @use HasFactory<WishlistFactory> */
    use HasFactory;
    use Auditable;

    protected static function newFactory(): WishlistFactory
    {
        return WishlistFactory::new();
    }
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
