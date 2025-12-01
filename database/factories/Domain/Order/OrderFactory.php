<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Order;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 100000);
        $taxAmount = (int) ($subtotal * 0.18);
        $shippingAmount = $this->faker->randomElement([0, 500, 1000, 1500]);

        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('????-####')),
            'status' => $this->faker->randomElement(OrderStatus::cases())->value,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => 0,
            'total_amount' => $subtotal + $taxAmount + $shippingAmount,
            'currency' => 'TRY',
            'shipping_name' => $this->faker->name(),
            'shipping_phone' => $this->faker->phoneNumber(),
            'shipping_address' => $this->faker->address(),
            'shipping_city' => $this->faker->city(),
            'shipping_district' => $this->faker->citySuffix(),
            'shipping_postal_code' => $this->faker->postcode(),
            'shipping_country' => 'TR',
            'billing_name' => $this->faker->name(),
            'billing_phone' => $this->faker->phoneNumber(),
            'billing_address' => $this->faker->address(),
            'billing_city' => $this->faker->city(),
            'billing_district' => $this->faker->citySuffix(),
            'billing_postal_code' => $this->faker->postcode(),
            'billing_country' => 'TR',
            'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card', 'bank_transfer']),
            'payment_status' => 'pending',
            'paid_at' => null,
            'notes' => $this->faker->optional()->sentence(),
            'admin_notes' => null,
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
     * Set pending status.
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PENDING->value,
        ]);
    }

    /**
     * Set processing status.
     */
    public function processing(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PROCESSING->value,
        ]);
    }

    /**
     * Set completed status.
     */
    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DELIVERED->value,
        ]);
    }

    /**
     * Set cancelled status.
     */
    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    /**
     * Set shipped status.
     */
    public function shipped(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::SHIPPED->value,
        ]);
    }

    /**
     * Set a specific total amount.
     */
    public function withTotal(int $amount): self
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $amount,
        ]);
    }
}
