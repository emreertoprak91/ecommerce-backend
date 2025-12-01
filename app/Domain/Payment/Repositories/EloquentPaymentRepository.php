<?php

declare(strict_types=1);

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment
    {
        return Payment::with(['user', 'order'])->find($id);
    }

    public function findByMerchantOid(string $merchantOid): ?Payment
    {
        return Payment::with(['user', 'order'])
            ->where('merchant_oid', $merchantOid)
            ->first();
    }

    public function findByTransactionId(string $transactionId): ?Payment
    {
        return Payment::with(['user', 'order'])
            ->where('transaction_id', $transactionId)
            ->first();
    }

    public function findByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::with(['order'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);
        return $payment->fresh();
    }

    public function delete(Payment $payment): bool
    {
        return $payment->delete();
    }

    public function getPendingPayments(): Collection
    {
        return Payment::with(['user', 'order'])
            ->where('status', PaymentStatus::PENDING->value)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getCompletedPayments(int $userId): Collection
    {
        return Payment::with(['order'])
            ->where('user_id', $userId)
            ->where('status', PaymentStatus::COMPLETED->value)
            ->orderBy('completed_at', 'desc')
            ->get();
    }
}
