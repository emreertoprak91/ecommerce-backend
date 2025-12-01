<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Order\Repositories\EloquentOrderRepository;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Payment\Repositories\EloquentPaymentRepository;
use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;
use App\Domain\Product\Repositories\EloquentCategoryRepository;
use App\Domain\Product\Repositories\EloquentProductRepository;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\User\Repositories\EloquentUserRepository;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Wishlist\Repositories\EloquentWishlistRepository;
use App\Domain\Wishlist\Repositories\WishlistRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repository interfaces to their implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<string, string>
     */
    public array $bindings = [
        ProductRepositoryInterface::class => EloquentProductRepository::class,
        CategoryRepositoryInterface::class => EloquentCategoryRepository::class,
        OrderRepositoryInterface::class => EloquentOrderRepository::class,
        WishlistRepositoryInterface::class => EloquentWishlistRepository::class,
        PaymentRepositoryInterface::class => EloquentPaymentRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind ProductRepository with model injection
        $this->app->bind(ProductRepositoryInterface::class, function ($app) {
            return new EloquentProductRepository(new Product());
        });

        // Bind OrderRepository
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);

        // Bind WishlistRepository
        $this->app->bind(WishlistRepositoryInterface::class, EloquentWishlistRepository::class);

        // Bind PaymentRepository
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);

        // Bind CategoryRepository
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);

        // Bind UserRepository
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
