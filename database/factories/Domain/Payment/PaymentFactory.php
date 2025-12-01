<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Payment;

use App\Domain\Order\Models\Order;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(1000, 100000);

        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'merchant_oid' => 'PAY-' . strtoupper($this->faker->unique()->bothify('????-####-????')),
            'payment_provider' => 'paytr',
            'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card', 'bank_transfer']),
            'amount' => $amount,
            'payment_amount' => $amount * 100, // kuruş cinsinden
            'currency' => 'TRY',
            'status' => $this->faker->randomElement(PaymentStatus::cases())->value,
            'paytr_token' => $this->faker->optional()->sha256(),
            'transaction_id' => $this->faker->optional()->uuid(),
            'masked_pan' => $this->faker->optional()->numerify('####-****-****-####'),
            'installment_count' => $this->faker->optional()->randomElement(['0', '3', '6', '9', '12']),
            'provider_response' => null,
            'error_message' => null,
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_acceptance_ip' => $this->faker->ipv4(),
            'completed_at' => null,
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
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'payment_amount' => (int) ($order->total_amount * 100),
        ]);
    }

    /**
     * Set pending status.
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING->value,
            'completed_at' => null,
        ]);
    }

    /**
     * Set completed status.
     */
    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::COMPLETED->value,
            'completed_at' => now(),
            'transaction_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Set failed status.
     */
    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED->value,
            'error_message' => $this->faker->sentence(),
            'completed_at' => null,
        ]);
    }

    /**
     * Set refunded status.
     */
    public function refunded(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::REFUNDED->value,
        ]);
    }

    /**
     * Set a specific amount.
     */
    public function withAmount(int $amount): self
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'payment_amount' => $amount * 100,
        ]);
    }

    /**
     * Set PayTR token.
     */
    public function withPaytrToken(string $token): self
    {
        return $this->state(fn (array $attributes) => [
            'paytr_token' => $token,
        ]);
    }

    /**
     * Set merchant OID.
     */
    public function withMerchantOid(string $merchantOid): self
    {
        return $this->state(fn (array $attributes) => [
            'merchant_oid' => $merchantOid,
        ]);
    }

    /**
     * Set for a specific user.
     */
    public function forUser(User $user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
