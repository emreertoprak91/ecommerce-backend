<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\DTOs\RegisterUserDTO;
use App\Domain\User\Exceptions\EmailAlreadyExistsException;
use App\Domain\User\Exceptions\EmailNotVerifiedException;
use App\Domain\User\Exceptions\InvalidCredentialsException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Jobs\SendWelcomeEmailJob;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * User Service
 *
 * Handles all user-related business logic operations.
 */
final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Register a new user.
     *
     * @throws EmailAlreadyExistsException
     */
    public function register(RegisterUserDTO $dto): User
    {
        $this->logger->info('[UserService::register] Registering new user', [
            'email' => $dto->email,
        ]);

        // Check if email already exists
        if ($this->userRepository->emailExists($dto->email)) {
            $this->logger->warning('[UserService::register] Email already exists', [
                'email' => $dto->email,
            ]);
            throw new EmailAlreadyExistsException($dto->email);
        }

        return DB::transaction(function () use ($dto) {
            // Generate verification token
            $verificationToken = Str::random(64);

            $user = $this->userRepository->create([
                ...$dto->toArray(),
                'verification_token' => $verificationToken,
            ]);

            $this->logger->info('[UserService::register] User created, dispatching welcome email job', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Dispatch job to send welcome email
            SendWelcomeEmailJob::dispatch($user);

            $this->logger->info('[UserService::register] Welcome email job dispatched', [
                'user_id' => $user->id,
                'job' => 'SendWelcomeEmailJob',
            ]);

            return $user;
        });
    }

    /**
     * Verify user email by token.
     */
    public function verifyEmail(string $token): ?User
    {
        $user = $this->userRepository->findByVerificationToken($token);

        if (!$user) {
            $this->logger->warning('[UserService::verifyEmail] Invalid token', [
                'token' => substr($token, 0, 10) . '...',
            ]);
            return null;
        }

        $user->markEmailAsVerified();

        $this->logger->info('[UserService::verifyEmail] Email verified', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * Resend verification email.
     */
    public function resendVerificationEmail(User $user): void
    {
        if ($user->isEmailVerified()) {
            return;
        }

        // Generate new verification token
        $verificationToken = Str::random(64);
        $this->userRepository->update($user, ['verification_token' => $verificationToken]);

        // Dispatch job to resend email
        SendWelcomeEmailJob::dispatch($user->fresh() ?? $user);

        $this->logger->info('[UserService::resendVerificationEmail] Verification email job dispatched', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Authenticate user with credentials.
     *
     * @throws InvalidCredentialsException
     * @throws EmailNotVerifiedException
     */
    public function authenticate(string $email, string $password): User
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            $this->logger->warning('[UserService::authenticate] Invalid credentials', [
                'email' => $email,
            ]);
            throw new InvalidCredentialsException();
        }

        if (!$user->email_verified_at) {
            $this->logger->warning('[UserService::authenticate] Email not verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            throw new EmailNotVerifiedException($user->email);
        }

        $this->logger->info('[UserService::authenticate] User authenticated', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * Find user by email or throw exception.
     *
     * @throws UserNotFoundException
     */
    public function findByEmailOrFail(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new UserNotFoundException($email);
        }

        return $user;
    }

    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }
}
