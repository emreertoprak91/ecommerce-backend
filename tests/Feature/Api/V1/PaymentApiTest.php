<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Order\Models\Order;
use App\Domain\Payment\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Payment API Feature Tests
 *
 * End-to-end tests for the Payment API endpoints.
 */
final class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    // ==================== INITIATE PAYMENT ====================

    #[Test]
    public function it_requires_authentication_to_initiate_payment(): void
    {
        // Act
        $response = $this->postJson('/api/v1/payments/initiate', [
            'order_id' => 1,
        ]);

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_validates_order_id_when_initiating_payment(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initiate', []);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    #[Test]
    public function it_validates_order_exists_when_initiating_payment(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => 999999,
            ]);

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    public function it_cannot_initiate_payment_for_other_users_order(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $order = Order::factory()
            ->for($otherUser)
            ->create(['status' => 'pending']);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $order->id,
            ]);

        // Assert
        $response->assertStatus(403);
    }

    #[Test]
    public function it_cannot_initiate_payment_for_already_paid_order(): void
    {
        // Arrange
        $order = Order::factory()
            ->for($this->user)
            ->create([
                'status' => 'paid',
                'payment_status' => 'paid',
            ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $order->id,
            ]);

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    public function it_can_initiate_payment_for_pending_order(): void
    {
        // Arrange
        Http::fake([
            'https://www.paytr.com/odeme/api/get-token' => Http::response([
                'status' => 'success',
                'token' => 'test_token_12345',
            ], 200),
        ]);

        $order = Order::factory()
            ->for($this->user)
            ->create([
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => 10000,
            ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $order->id,
            ]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'merchant_oid',
                    'iframe_url',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'user_id' => $this->user->id,
        ]);
    }

    // ==================== PAYMENT NOTIFICATION (WEBHOOK) ====================

    #[Test]
    public function it_handles_successful_payment_notification(): void
    {
        // Arrange
        $order = Order::factory()
            ->for($this->user)
            ->create([
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => 10000,
            ]);

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'merchant_oid' => 'ORD123456789',
            'amount' => 10000,
            'status' => 'processing',
        ]);

        // PayTR hash calculation
        $merchantSalt = config('services.paytr.merchant_salt', 'test_salt');
        $merchantKey = config('services.paytr.merchant_key', 'test_key');
        $hashStr = $payment->merchant_oid . $merchantSalt . 'success' . 10000;
        $hash = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));

        // Act
        $response = $this->postJson('/api/v1/payments/notify', [
            'merchant_oid' => $payment->merchant_oid,
            'status' => 'success',
            'total_amount' => 10000,
            'hash' => $hash,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    #[Test]
    public function it_returns_ok_for_invalid_hash_to_prevent_retry(): void
    {
        // Act
        $response = $this->postJson('/api/v1/payments/notify', [
            'merchant_oid' => 'INVALID123',
            'status' => 'success',
            'total_amount' => 10000,
            'hash' => 'invalid_hash',
        ]);

        // Assert - PayTR expects OK or specific error codes
        $response->assertStatus(400);
    }

    // ==================== PAYMENT STATUS ====================

    #[Test]
    public function it_requires_authentication_to_check_payment_status(): void
    {
        // Act
        $response = $this->getJson('/api/v1/payments/TEST123/status');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_get_payment_status(): void
    {
        // Arrange
        $order = Order::factory()
            ->for($this->user)
            ->create();

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'merchant_oid' => 'ORD123STATUS',
            'status' => 'completed',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/payments/{$payment->merchant_oid}/status");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    #[Test]
    public function it_returns_404_when_payment_not_found(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/NONEXISTENT/status');

        // Assert
        $response->assertStatus(404);
    }

    #[Test]
    public function it_cannot_view_other_users_payment_status(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $order = Order::factory()
            ->for($otherUser)
            ->create();

        $payment = Payment::factory()->create([
            'user_id' => $otherUser->id,
            'order_id' => $order->id,
            'merchant_oid' => 'ORD123OTHER',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/payments/{$payment->merchant_oid}/status");

        // Assert
        $response->assertStatus(403);
    }
}
