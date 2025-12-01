<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Wishlist\Services;

use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\Wishlist\Models\Wishlist;
use App\Domain\Wishlist\Repositories\WishlistRepositoryInterface;
use App\Domain\Wishlist\Services\WishlistService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * WishlistService Unit Tests
 *
 * İzole birim testleri - Repository mocklanır, sadece Service'in iş mantığı test edilir.
 */
final class WishlistServiceTest extends TestCase
{
    private WishlistService $service;
    private MockInterface&WishlistRepositoryInterface $repository;
    private MockInterface&ProductRepositoryInterface $productRepository;
    private MockInterface&LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockInterface&WishlistRepositoryInterface $repository */
        $repository = Mockery::mock(WishlistRepositoryInterface::class);
        $this->repository = $repository;

        /** @var MockInterface&ProductRepositoryInterface $productRepository */
        $productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->productRepository = $productRepository;

        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->andReturnNull();
        $logger->shouldReceive('warning')->andReturnNull();
        $this->logger = $logger;

        $this->service = new WishlistService(
            $this->repository,
            $this->productRepository,
            $this->logger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== GET USER WISHLIST ====================

    #[Test]
    public function it_can_get_user_wishlist(): void
    {
        // Arrange
        $userId = 1;
        $perPage = 15;
        $paginator = new LengthAwarePaginator([], 0, $perPage);

        $this->repository
            ->shouldReceive('getUserWishlist')
            ->once()
            ->with($userId, $perPage)
            ->andReturn($paginator);

        // Act
        $result = $this->service->getUserWishlist($userId, $perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ==================== ADD TO WISHLIST ====================
    // Note: addToWishlist directly calls Product::find(), which makes it
    // better suited for integration tests. Unit tests for this method
    // would require extensive model mocking.

    // ==================== REMOVE FROM WISHLIST ====================

    #[Test]
    public function it_can_remove_product_from_wishlist(): void
    {
        // Arrange
        $userId = 1;
        $productId = 10;

        $this->repository
            ->shouldReceive('remove')
            ->once()
            ->with($userId, $productId)
            ->andReturn(true);

        // Act
        $result = $this->service->removeFromWishlist($userId, $productId);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_removing_non_existent_item(): void
    {
        // Arrange
        $userId = 1;
        $productId = 999;

        $this->repository
            ->shouldReceive('remove')
            ->once()
            ->with($userId, $productId)
            ->andReturn(false);

        // Act
        $result = $this->service->removeFromWishlist($userId, $productId);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== TOGGLE WISHLIST ====================
    // Note: toggleWishlist calls addToWishlist which uses Product::find(),
    // making it better suited for integration tests.

    // ==================== CHECK IN WISHLIST ====================

    #[Test]
    public function it_can_check_if_product_is_in_wishlist(): void
    {
        // Arrange
        $userId = 1;
        $productId = 10;

        $this->repository
            ->shouldReceive('isInWishlist')
            ->once()
            ->with($userId, $productId)
            ->andReturn(true);

        // Act
        $result = $this->service->isInWishlist($userId, $productId);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_product_not_in_wishlist(): void
    {
        // Arrange
        $userId = 1;
        $productId = 10;

        $this->repository
            ->shouldReceive('isInWishlist')
            ->once()
            ->with($userId, $productId)
            ->andReturn(false);

        // Act
        $result = $this->service->isInWishlist($userId, $productId);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== GET WISHLIST COUNT ====================

    #[Test]
    public function it_can_get_wishlist_count(): void
    {
        // Arrange
        $userId = 1;

        $this->repository
            ->shouldReceive('getCount')
            ->once()
            ->with($userId)
            ->andReturn(5);

        // Act
        $result = $this->service->getWishlistCount($userId);

        // Assert
        $this->assertEquals(5, $result);
    }

    // ==================== CLEAR WISHLIST ====================

    #[Test]
    public function it_can_clear_user_wishlist(): void
    {
        // Arrange
        $userId = 1;

        $this->repository
            ->shouldReceive('clear')
            ->once()
            ->with($userId)
            ->andReturn(3);

        // Act
        $result = $this->service->clearWishlist($userId);

        // Assert
        $this->assertEquals(3, $result);
    }
}
