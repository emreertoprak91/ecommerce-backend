<?php

declare(strict_types=1);

namespace Tests\Integration\Domain\Wishlist\Repositories;

use App\Domain\Product\Models\Product;
use App\Domain\Wishlist\Models\Wishlist;
use App\Domain\Wishlist\Repositories\EloquentWishlistRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EloquentWishlistRepository Integration Tests
 *
 * Gerçek veritabanı işlemlerini test eder. Repository metodlarının
 * doğru şekilde çalıştığını doğrular.
 */
final class WishlistRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentWishlistRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentWishlistRepository();
    }

    // ==================== GET USER WISHLIST ====================

    #[Test]
    public function it_can_get_user_wishlist_with_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();
        $products = Product::factory()->count(5)->create();

        foreach ($products as $product) {
            Wishlist::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $result = $this->repository->getUserWishlist($user->id, 3);

        // Assert
        $this->assertEquals(5, $result->total());
        $this->assertEquals(3, $result->perPage());
        $this->assertCount(3, $result->items());
    }

    #[Test]
    public function it_loads_product_relation_with_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product']);

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $result = $this->repository->getUserWishlist($user->id, 15);

        // Assert
        $this->assertTrue($result->first()->relationLoaded('product'));
        $this->assertEquals('Test Product', $result->first()->product->name);
    }

    #[Test]
    public function it_only_returns_current_user_wishlist_items(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product2->id]);

        // Act
        $result = $this->repository->getUserWishlist($user1->id, 15);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals($product1->id, $result->first()->product_id);
    }

    // ==================== GET ALL USER WISHLIST ====================

    #[Test]
    public function it_can_get_all_user_wishlist_without_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();
        $products = Product::factory()->count(5)->create();

        foreach ($products as $product) {
            Wishlist::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $result = $this->repository->getAllUserWishlist($user->id);

        // Assert
        $this->assertCount(5, $result);
    }

    // ==================== FIND BY USER AND PRODUCT ====================

    #[Test]
    public function it_can_find_wishlist_item_by_user_and_product(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $result = $this->repository->findByUserAndProduct($user->id, $product->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($wishlist->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_wishlist_item_not_found(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->findByUserAndProduct($user->id, 999);

        // Assert
        $this->assertNull($result);
    }

    // ==================== ADD ====================

    #[Test]
    public function it_can_add_product_to_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Act
        $result = $this->repository->add($user->id, $product->id);

        // Assert
        $this->assertInstanceOf(Wishlist::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($product->id, $result->product_id);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function it_does_not_duplicate_when_adding_same_product(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Act - Add twice
        $result1 = $this->repository->add($user->id, $product->id);
        $result2 = $this->repository->add($user->id, $product->id);

        // Assert
        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals(1, $this->repository->getCount($user->id));
    }

    // ==================== REMOVE ====================

    #[Test]
    public function it_can_remove_product_from_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $result = $this->repository->remove($user->id, $product->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function it_returns_false_when_removing_non_existent_item(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->remove($user->id, 999);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== IS IN WISHLIST ====================

    #[Test]
    public function it_can_check_if_product_is_in_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // Act
        $result = $this->repository->isInWishlist($user->id, $product->id);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_product_not_in_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Act
        $result = $this->repository->isInWishlist($user->id, $product->id);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== GET COUNT ====================

    #[Test]
    public function it_can_get_wishlist_count(): void
    {
        // Arrange
        $user = User::factory()->create();
        $products = Product::factory()->count(5)->create();

        foreach ($products as $product) {
            Wishlist::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $result = $this->repository->getCount($user->id);

        // Assert
        $this->assertEquals(5, $result);
    }

    #[Test]
    public function it_returns_zero_when_wishlist_empty(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->getCount($user->id);

        // Assert
        $this->assertEquals(0, $result);
    }

    // ==================== CLEAR WISHLIST ====================

    #[Test]
    public function it_can_clear_user_wishlist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $products = Product::factory()->count(3)->create();

        foreach ($products as $product) {
            Wishlist::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        // Act
        $result = $this->repository->clear($user->id);

        // Assert
        $this->assertEquals(3, $result);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_does_not_affect_other_users_wishlist_when_clearing(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product2->id]);

        // Act
        $this->repository->clear($user1->id);

        // Assert
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user1->id]);
        $this->assertDatabaseHas('wishlists', ['user_id' => $user2->id]);
    }
}
