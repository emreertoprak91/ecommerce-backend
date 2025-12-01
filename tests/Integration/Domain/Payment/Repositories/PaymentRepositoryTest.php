<?php

declare(strict_types=1);

namespace Tests\Integration\Domain\Payment\Repositories;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Models\Order;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\EloquentPaymentRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EloquentPaymentRepository Integration Tests
 *
 * Gerçek veritabanı işlemlerini test eder. Payment repository metodlarının
 * doğru şekilde çalıştığını doğrular.
 */
final class PaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentRepository();
    }

    // ==================== CREATE ====================

    #[Test]
    public function it_can_create_payment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'merchant_oid' => 'TEST-123',
            'amount' => 10000,
            'payment_amount' => 1000000,
            'status' => PaymentStatus::PENDING->value,
            'payment_method' => 'credit_card',
        ]);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals('TEST-123', $result->merchant_oid);
        $this->assertEquals(10000, $result->amount);
        $this->assertEquals(PaymentStatus::PENDING->value, $result->status);

        $this->assertDatabaseHas('payments', [
            'merchant_oid' => 'TEST-123',
            'order_id' => $order->id,
        ]);
    }

    // ==================== FIND BY ID ====================

    #[Test]
    public function it_can_find_payment_by_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        // Act
        $result = $this->repository->findById($payment->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($payment->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_payment_not_found(): void
    {
        // Act
        $result = $this->repository->findById(99999);

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND BY MERCHANT OID ====================

    #[Test]
    public function it_can_find_payment_by_merchant_oid(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'merchant_oid' => 'MERCHANT-OID-123',
        ]);

        // Act
        $result = $this->repository->findByMerchantOid('MERCHANT-OID-123');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($payment->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_merchant_oid_not_found(): void
    {
        // Act
        $result = $this->repository->findByMerchantOid('NON-EXISTENT-OID');

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND BY TRANSACTION ID ====================

    #[Test]
    public function it_can_find_payment_by_transaction_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'transaction_id' => 'TXN-12345',
        ]);

        // Act
        $result = $this->repository->findByTransactionId('TXN-12345');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($payment->id, $result->id);
    }

    // ==================== FIND BY USER ID ====================

    #[Test]
    public function it_can_find_user_payments_with_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $order = Order::factory()->create(['user_id' => $user->id]);
            Payment::factory()->create([
                'user_id' => $user->id,
                'order_id' => $order->id,
            ]);
        }

        // Act
        $result = $this->repository->findByUserId($user->id, 3);

        // Assert
        $this->assertEquals(5, $result->total());
        $this->assertCount(3, $result->items());
    }

    // ==================== UPDATE ====================

    #[Test]
    public function it_can_update_payment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
        ]);

        // Act
        $result = $this->repository->update($payment, [
            'status' => PaymentStatus::COMPLETED->value,
            'transaction_id' => 'NEW-TXN-123',
        ]);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(PaymentStatus::COMPLETED->value, $result->status);
        $this->assertEquals('NEW-TXN-123', $result->transaction_id);
    }

    // ==================== DELETE ====================

    #[Test]
    public function it_can_delete_payment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        // Act
        $result = $this->repository->delete($payment);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    // ==================== GET COMPLETED PAYMENTS ====================

    #[Test]
    public function it_can_get_completed_payments_for_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Create completed payments
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create(['user_id' => $user->id]);
            Payment::factory()->completed()->create([
                'user_id' => $user->id,
                'order_id' => $order->id,
            ]);
        }

        // Create pending payment
        $pendingOrder = Order::factory()->create(['user_id' => $user->id]);
        Payment::factory()->pending()->create([
            'user_id' => $user->id,
            'order_id' => $pendingOrder->id,
        ]);

        // Act
        $result = $this->repository->getCompletedPayments($user->id);

        // Assert
        $this->assertCount(3, $result);
        $result->each(function ($payment) {
            $this->assertEquals(PaymentStatus::COMPLETED->value, $payment->status);
        });
    }

    // ==================== LOAD RELATIONS ====================

    #[Test]
    public function it_can_load_order_relation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-456',
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        // Act
        $result = $this->repository->findById($payment->id);

        // Assert
        $this->assertTrue($result->relationLoaded('order'));
        $this->assertEquals('ORD-TEST-456', $result->order->order_number);
    }

    #[Test]
    public function it_can_load_user_relation(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        $order = Order::factory()->create(['user_id' => $user->id]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        // Act
        $result = $this->repository->findById($payment->id);

        // Assert
        $this->assertTrue($result->relationLoaded('user'));
        $this->assertEquals('Test User', $result->user->name);
    }

    // ==================== UNIQUE MERCHANT OID ====================

    #[Test]
    public function merchant_oid_should_be_unique(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order1->id,
            'merchant_oid' => 'UNIQUE-OID-123',
        ]);

        // Assert - Attempting to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order2->id,
            'merchant_oid' => 'UNIQUE-OID-123',
        ]);
    }

    // ==================== PAYMENT STATUS TRANSITIONS ====================

    #[Test]
    public function it_tracks_payment_status_from_pending_to_completed(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
        ]);

        $payment = Payment::factory()->pending()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        // Act - Update payment status
        $updatedPayment = $this->repository->update($payment, [
            'status' => PaymentStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);

        // Assert
        $this->assertEquals(PaymentStatus::COMPLETED->value, $updatedPayment->status);
    }

    #[Test]
    public function it_tracks_payment_status_from_pending_to_failed(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $payment = Payment::factory()->pending()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        // Act
        $updatedPayment = $this->repository->update($payment, [
            'status' => PaymentStatus::FAILED->value,
            'error_message' => 'Payment declined',
        ]);

        // Assert
        $this->assertEquals(PaymentStatus::FAILED->value, $updatedPayment->status);
        $this->assertEquals('Payment declined', $updatedPayment->error_message);
    }
}
