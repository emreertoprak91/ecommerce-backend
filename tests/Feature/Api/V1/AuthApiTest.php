<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Authentication API Feature Tests
 */
final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    // ==================== REGISTER ====================

    #[Test]
    public function user_can_register(): void
    {
        // Arrange
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/v1/auth/register', $payload);

        // Assert
        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function register_validates_required_fields(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/register', []);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function register_validates_unique_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function register_validates_password_confirmation(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // ==================== LOGIN ====================

    #[Test]
    public function user_can_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    }

    #[Test]
    public function user_cannot_login_with_invalid_credentials(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function user_cannot_login_with_non_existent_email(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ==================== ME (GET CURRENT USER) ====================

    #[Test]
    public function authenticated_user_can_get_own_info(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/v1/auth/me');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_get_own_info(): void
    {
        // Act
        $response = $this->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    // ==================== LOGOUT ====================

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function authenticated_user_can_logout_from_all_devices(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->createToken('token-1');
        $user->createToken('token-2');
        $user->createToken('token-3');

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/auth/logout-all');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out from all devices successfully',
            ]);

        // Verify all tokens are revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    // ==================== TOKEN AUTHENTICATION ====================

    #[Test]
    public function user_can_access_protected_route_with_valid_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(200);
    }

    #[Test]
    public function user_cannot_access_protected_route_with_invalid_token(): void
    {
        // Act
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(401);
    }
}
