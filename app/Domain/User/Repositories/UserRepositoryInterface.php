<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * User Repository Interface
 *
 * Defines the contract for user data persistence operations.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by verification token.
     */
    public function findByVerificationToken(string $token): ?User;

    /**
     * Check if email exists.
     */
    public function emailExists(string $email): bool;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update user.
     */
    public function update(User $user, array $data): User;

    /**
     * Delete user.
     */
    public function delete(User $user): bool;

    /**
     * Get paginated users.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
