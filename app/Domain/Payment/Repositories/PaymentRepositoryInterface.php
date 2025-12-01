<?php

declare(strict_types=1);

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment;

    public function findByMerchantOid(string $merchantOid): ?Payment;

    public function findByTransactionId(string $transactionId): ?Payment;

    public function findByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Payment;

    public function update(Payment $payment, array $data): Payment;

    public function delete(Payment $payment): bool;

    public function getPendingPayments(): Collection;

    public function getCompletedPayments(int $userId): Collection;
}
