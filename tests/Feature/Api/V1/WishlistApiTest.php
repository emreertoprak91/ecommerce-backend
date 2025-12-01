<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Product\Models\Product;
use App\Domain\Wishlist\Models\Wishlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Wishlist API Feature Tests
 *
 * End-to-end tests for the Wishlist API endpoints.
 */
final class WishlistApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    // ==================== LIST WISHLIST ====================

    #[Test]
    public function it_requires_authentication_to_list_wishlist(): void
    {
        // Act
        $response = $this->getJson('/api/v1/wishlist');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_list_user_wishlist(): void
    {
        // Arrange
        $products = Product::factory()->count(3)->create();
        foreach ($products as $product) {
            Wishlist::factory()->create([
                'user_id' => $this->user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/wishlist');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'product_id', 'product', 'added_at'],
                ],
            ])
            ->assertJsonCount(3, 'data')
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function it_returns_empty_array_when_wishlist_is_empty(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/wishlist');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_only_returns_current_user_wishlist(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product1->id,
        ]);
        Wishlist::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $product2->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/wishlist');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $product1->id);
    }

    // ==================== ADD TO WISHLIST ====================

    #[Test]
    public function it_can_add_product_to_wishlist(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/wishlist', [
                'product_id' => $product->id,
            ]);

        // Assert
        $response
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function it_returns_existing_item_when_product_already_in_wishlist(): void
    {
        // Arrange
        $product = Product::factory()->create();
        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/wishlist', [
                'product_id' => $product->id,
            ]);

        // Assert - Controller returns 201 for add requests (uses firstOrCreate internally)
        $response->assertStatus(201);

        $this->assertDatabaseCount('wishlists', 1);
    }

    #[Test]
    public function it_validates_product_id_when_adding_to_wishlist(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/wishlist', [
                'product_id' => 999999,
            ]);

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    public function it_requires_product_id_when_adding_to_wishlist(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/wishlist', []);

        // Assert
        $response->assertStatus(422);
    }

    // ==================== REMOVE FROM WISHLIST ====================

    #[Test]
    public function it_can_remove_product_from_wishlist(): void
    {
        // Arrange
        $product = Product::factory()->create();
        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/wishlist/{$product->id}");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function it_returns_not_found_when_removing_non_existent_wishlist_item(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/wishlist/{$product->id}");

        // Assert - Controller returns 404 when item not in wishlist
        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_not_found_when_removing_other_users_wishlist_item(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $product = Product::factory()->create();
        Wishlist::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $product->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/wishlist/{$product->id}");

        // Assert - Returns 404 because this user doesn't have it in their wishlist
        $response->assertStatus(404);

        // Other user's wishlist item should still exist
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $otherUser->id,
            'product_id' => $product->id,
        ]);
    }

    // ==================== TOGGLE WISHLIST ====================

    #[Test]
    public function it_can_toggle_add_product_to_wishlist(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/wishlist/toggle', [
                'product_id' => $product->id,
            ]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.added', true)
            ->assertJsonPath('data.in_wishlist', true);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function it_can_toggle_remove_product_from_wishlist(): void
    {
        // Arrange
        $product = Product::factory()->create();
        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/wishlist/toggle', [
                'product_id' => $product->id,
            ]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.added', false)
            ->assertJsonPath('data.in_wishlist', false);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    // ==================== CHECK WISHLIST ====================

    #[Test]
    public function it_can_check_if_product_is_in_wishlist(): void
    {
        // Arrange
        $product = Product::factory()->create();
        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/wishlist/check/{$product->id}");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.in_wishlist', true);
    }

    #[Test]
    public function it_returns_false_when_product_not_in_wishlist(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/wishlist/check/{$product->id}");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.in_wishlist', false);
    }

    // ==================== WISHLIST COUNT ====================

    #[Test]
    public function it_can_get_wishlist_count(): void
    {
        // Arrange
        $products = Product::factory()->count(5)->create();
        foreach ($products as $product) {
            Wishlist::factory()->create([
                'user_id' => $this->user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/wishlist/count');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.count', 5);
    }

    #[Test]
    public function it_returns_zero_count_for_empty_wishlist(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/wishlist/count');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.count', 0);
    }

    // ==================== CLEAR WISHLIST ====================

    #[Test]
    public function it_can_clear_wishlist(): void
    {
        // Arrange
        $products = Product::factory()->count(3)->create();
        foreach ($products as $product) {
            Wishlist::factory()->create([
                'user_id' => $this->user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/wishlist');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.items_removed', 3);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->user->id,
        ]);
    }
}
