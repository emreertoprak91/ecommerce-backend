<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\User\DTOs\RegisterUserDTO;
use App\Domain\User\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResendVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authentication Controller
 *
 * Handles user authentication via Sanctum.
 *
 * @group Authentication
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Register a new user.
     *
     * @bodyParam name string required User's full name. Example: John Doe
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string required Password (min 8 characters). Example: password123
     * @bodyParam password_confirmation string required Password confirmation.
     *
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - EmailAlreadyExistsException → 422 validation error
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterUserDTO::fromArray($request->validated());
        $user = $this->userService->register($dto);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->createdResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => false,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Please check your email to verify your account.',
        ], 'User registered successfully. Verification email sent.');
    }

    /**
     * Verify user email.
     *
     * @OA\Get(
     *     path="/api/v1/auth/verify-email/{token}",
     *     tags={"Authentication"},
     *     summary="Verify user email address",
     *     @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Email verified successfully"),
     *     @OA\Response(response=400, description="Invalid or expired token")
     * )
     */
    public function verifyEmail(string $token): JsonResponse
    {
        $user = $this->userService->verifyEmail($token);

        if (!$user) {
            return $this->errorResponse('Invalid or expired verification token', 400);
        }

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => true,
            ],
        ], 'Email verified successfully');
    }

    /**
     * Resend verification email.
     *
     * @OA\Post(
     *     path="/api/v1/auth/resend-verification",
     *     tags={"Authentication"},
     *     summary="Resend verification email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Verification email sent"),
     *     @OA\Response(response=404, description="User not found")
     * )
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - UserNotFoundException → 404
     */
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $user = $this->userService->findByEmailOrFail($request->validated()['email']);

        if ($user->email_verified_at) {
            return $this->errorResponse('Email is already verified', 400);
        }

        $this->userService->resendVerificationEmail($user);

        return $this->successResponse(null, 'Verification email sent');
    }

    /**
     * Login user and get token.
     *
     * @bodyParam email string required User's email. Example: john@example.com
     * @bodyParam password string required User's password. Example: password123
     *
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=422, description="Invalid credentials")
     * )
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - InvalidCredentialsException → 422 validation error
     * - EmailNotVerifiedException → 422 with email_verified=false
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->userService->authenticate($validated['email'], $validated['password']);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Get authenticated user.
     *
     * @authenticated
     *
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User retrieved"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'email_verified' => $request->user()->email_verified_at !== null,
            'email_verified_at' => $request->user()->email_verified_at,
            'created_at' => $request->user()->created_at->toISOString(),
        ], 'User retrieved successfully');
    }

    /**
     * Logout user (revoke current token).
     *
     * @authenticated
     *
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Logout from all devices (revoke all tokens).
     *
     * @authenticated
     *
     * @OA\Post(
     *     path="/api/v1/auth/logout-all",
     *     tags={"Authentication"},
     *     summary="Logout from all devices",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out from all devices"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse(null, 'Logged out from all devices successfully');
    }
}
