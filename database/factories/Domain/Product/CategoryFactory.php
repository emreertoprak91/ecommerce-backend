<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Product;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Category Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Product\Models\Category>
 */
final class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional(0.7)->sentence(),
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category has a parent.
     */
    public function withParent(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }
}
