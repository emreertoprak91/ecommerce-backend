<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Eloquent User Repository
 *
 * Implementation of UserRepositoryInterface using Eloquent ORM.
 */
final class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByVerificationToken(string $token): ?User
    {
        return User::where('verification_token', $token)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh() ?? $user;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::paginate($perPage);
    }
}
