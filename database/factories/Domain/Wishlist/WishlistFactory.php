<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Wishlist;

use App\Domain\Product\Models\Product;
use App\Domain\Wishlist\Models\Wishlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wishlist>
 */
final class WishlistFactory extends Factory
{
    protected $model = Wishlist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set a specific user.
     */
    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set a specific product.
     */
    public function forProduct(Product $product): self
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
