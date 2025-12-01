<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\Observers;

use App\Domain\Order\Models\Order;
use App\Domain\Payment\Models\Payment;
use App\Domain\Product\Models\Product;
use App\Domain\Shared\Models\AuditLog;
use App\Domain\Shared\Observers\AuditObserver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Audit Observer Unit Tests
 *
 * Tests that the AuditObserver correctly logs model changes.
 */
final class AuditObserverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_user_creation(): void
    {
        // Act
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'event' => AuditLog::EVENT_CREATED,
        ]);

        $auditLog = AuditLog::where('model_type', User::class)
            ->where('model_id', $user->id)
            ->where('event', AuditLog::EVENT_CREATED)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('Test User', $auditLog->new_values['name']);
        $this->assertEquals('test@example.com', $auditLog->new_values['email']);
        // Password should be excluded from audit log
        $this->assertArrayNotHasKey('password', $auditLog->new_values);
    }

    #[Test]
    public function it_logs_user_update(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Original Name',
        ]);

        // Clear the creation log
        AuditLog::truncate();

        // Act
        $user->update(['name' => 'Updated Name']);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'event' => AuditLog::EVENT_UPDATED,
        ]);

        $auditLog = AuditLog::where('model_type', User::class)
            ->where('event', AuditLog::EVENT_UPDATED)
            ->first();

        $this->assertEquals('Original Name', $auditLog->old_values['name']);
        $this->assertEquals('Updated Name', $auditLog->new_values['name']);
    }

    #[Test]
    public function it_logs_user_deletion(): void
    {
        // Arrange
        $user = User::factory()->create();
        $userId = $user->id;

        // Clear the creation log
        AuditLog::truncate();

        // Act
        $user->delete();

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => User::class,
            'model_id' => $userId,
            'event' => AuditLog::EVENT_DELETED,
        ]);
    }

    #[Test]
    public function it_logs_order_creation(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Clear user creation log
        AuditLog::truncate();

        // Act
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'TEST-ORDER-123',
            'status' => 'pending',
        ]);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Order::class,
            'model_id' => $order->id,
            'event' => AuditLog::EVENT_CREATED,
        ]);

        $auditLog = AuditLog::where('model_type', Order::class)->first();
        $this->assertEquals('TEST-ORDER-123', $auditLog->new_values['order_number']);
    }

    #[Test]
    public function it_logs_order_status_update(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Clear previous logs
        AuditLog::truncate();

        // Act
        $order->update(['status' => 'paid']);

        // Assert
        $auditLog = AuditLog::where('model_type', Order::class)
            ->where('event', AuditLog::EVENT_UPDATED)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('pending', $auditLog->old_values['status']);
        $this->assertEquals('paid', $auditLog->new_values['status']);
    }

    #[Test]
    public function it_logs_product_creation(): void
    {
        // Arrange & Act
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'price' => 10000,
        ]);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Product::class,
            'model_id' => $product->id,
            'event' => AuditLog::EVENT_CREATED,
        ]);
    }

    #[Test]
    public function it_logs_payment_creation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Clear previous logs
        AuditLog::truncate();

        // Act
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        // Assert
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Payment::class,
            'model_id' => $payment->id,
            'event' => AuditLog::EVENT_CREATED,
        ]);

        $auditLog = AuditLog::where('model_type', Payment::class)->first();
        // paytr_token should be excluded from audit log
        $this->assertArrayNotHasKey('paytr_token', $auditLog->new_values ?? []);
    }

    #[Test]
    public function it_excludes_sensitive_fields_from_user_audit(): void
    {
        // Act
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        // Assert
        $auditLog = AuditLog::where('model_type', User::class)->first();
        $this->assertArrayNotHasKey('password', $auditLog->new_values);
        $this->assertArrayNotHasKey('remember_token', $auditLog->new_values);
        $this->assertArrayNotHasKey('verification_token', $auditLog->new_values);
    }

    #[Test]
    public function it_records_ip_address_and_user_agent(): void
    {
        // Arrange
        $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.100',
            'HTTP_USER_AGENT' => 'TestBrowser/1.0',
        ]);

        // Act
        $user = User::factory()->create();

        // Assert
        $auditLog = AuditLog::where('model_type', User::class)->first();
        $this->assertNotNull($auditLog->ip_address);
    }

    #[Test]
    public function it_can_retrieve_audit_logs_for_model(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->update(['name' => 'Updated Name']);
        $user->update(['name' => 'Another Update']);

        // Act
        $auditLogs = $user->auditLogs()->get();

        // Assert
        $this->assertCount(3, $auditLogs); // created + 2 updates
    }

    #[Test]
    public function it_does_not_log_when_no_changes_made(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        AuditLog::truncate();

        // Act - Update with same value (no actual change)
        $user->update(['name' => 'Test User']);

        // Assert - Should not create a new audit log
        $this->assertDatabaseMissing('audit_logs', [
            'model_type' => User::class,
            'event' => AuditLog::EVENT_UPDATED,
        ]);
    }
}
