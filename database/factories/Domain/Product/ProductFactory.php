<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Product;

use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Product Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Product\Models\Product>
 */
final class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->numberBetween(1000, 500000); // 10 TL - 5000 TL in cents

        return [
            'uuid' => (string) Str::uuid(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'sku' => strtoupper(fake()->unique()->bothify('???-###-???')),
            'description' => fake()->paragraphs(3, true),
            'price' => $price,
            'compare_price' => fake()->optional(0.3)->numberBetween($price, $price * 1.5),
            'cost' => fake()->optional(0.5)->numberBetween($price * 0.3, $price * 0.7),
            'quantity' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(90),
            'is_featured' => fake()->boolean(10),
            'meta_title' => fake()->optional(0.5)->sentence(),
            'meta_description' => fake()->optional(0.5)->text(160),
            'published_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product has a discount.
     */
    public function withDiscount(int $percentage = 20): static
    {
        return $this->state(function (array $attributes) use ($percentage) {
            $comparePrice = $attributes['price'] * (100 / (100 - $percentage));
            return [
                'compare_price' => (int) $comparePrice,
            ];
        });
    }
}
