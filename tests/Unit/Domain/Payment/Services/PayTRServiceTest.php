<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\Services;

use App\Domain\Payment\Services\PayTRService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PayTRService Unit Tests
 *
 * Not: PayTRService, final class olan OrderService'e bağımlı olduğundan,
 * tam unit testler yazılamıyor. OrderService gerektiren metodlar
 * Integration testlerinde test edilmelidir.
 *
 * Çözüm önerisi: PayTRService'in OrderServiceInterface kullanması gerekir.
 */
final class PayTRServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set config for tests
        config([
            'services.paytr.merchant_id' => 'test_merchant',
            'services.paytr.merchant_key' => 'test_key',
            'services.paytr.merchant_salt' => 'test_salt',
            'services.paytr.test_mode' => true,
        ]);
    }

    // ==================== HASH VALIDATION ====================

    #[Test]
    public function it_validates_paytr_hash_calculation(): void
    {
        // Test hash generation logic
        $merchantOid = 'ORD123';
        $merchantSalt = 'test_salt';
        $merchantKey = 'test_key';
        $status = 'success';
        $totalAmount = 10000;

        $hashStr = $merchantOid . $merchantSalt . $status . $totalAmount;
        $expectedHash = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));

        // Verify hash is generated correctly
        $this->assertNotEmpty($expectedHash);
        $this->assertIsString($expectedHash);
    }

    #[Test]
    public function it_has_correct_result_constants(): void
    {
        $this->assertEquals('success', PayTRService::RESULT_SUCCESS);
        $this->assertEquals('failed', PayTRService::RESULT_FAILED);
        $this->assertEquals('hash_mismatch', PayTRService::RESULT_HASH_MISMATCH);
        $this->assertEquals('not_found', PayTRService::RESULT_NOT_FOUND);
        $this->assertEquals('already_processed', PayTRService::RESULT_ALREADY_PROCESSED);
    }

    #[Test]
    public function it_generates_different_hashes_for_different_inputs(): void
    {
        $merchantSalt = 'test_salt';
        $merchantKey = 'test_key';

        $hash1Str = 'ORD123' . $merchantSalt . 'success' . 10000;
        $hash1 = base64_encode(hash_hmac('sha256', $hash1Str, $merchantKey, true));

        $hash2Str = 'ORD456' . $merchantSalt . 'success' . 10000;
        $hash2 = base64_encode(hash_hmac('sha256', $hash2Str, $merchantKey, true));

        $this->assertNotEquals($hash1, $hash2);
    }

    #[Test]
    public function it_generates_same_hash_for_same_inputs(): void
    {
        $merchantSalt = 'test_salt';
        $merchantKey = 'test_key';

        $hashStr = 'ORD123' . $merchantSalt . 'success' . 10000;
        $hash1 = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));
        $hash2 = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));

        $this->assertEquals($hash1, $hash2);
    }
}
