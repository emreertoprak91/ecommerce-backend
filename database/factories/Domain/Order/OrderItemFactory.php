<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Order;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = $this->faker->numberBetween(500, 10000);
        $quantity = $this->faker->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => $this->faker->words(3, true),
            'product_sku' => strtoupper($this->faker->bothify('SKU-????-####')),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set a specific order.
     */
    public function forOrder(Order $order): self
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Set a specific product.
     */
    public function forProduct(Product $product): self
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $product->price,
        ]);
    }

    /**
     * Set quantity and recalculate total.
     */
    public function withQuantity(int $quantity): self
    {
        return $this->state(function (array $attributes) use ($quantity) {
            return [
                'quantity' => $quantity,
                'total_price' => $attributes['unit_price'] * $quantity,
            ];
        });
    }

    /**
     * Set unit price and recalculate total.
     */
    public function withUnitPrice(int $unitPrice): self
    {
        return $this->state(function (array $attributes) use ($unitPrice) {
            return [
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $attributes['quantity'],
            ];
        });
    }
}
