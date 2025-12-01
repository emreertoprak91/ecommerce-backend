<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Payment\Models\Payment;
use App\Domain\Product\Events\ProductCreatedEvent;
use App\Domain\Product\Events\ProductDeletedEvent;
use App\Domain\Product\Events\ProductUpdatedEvent;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Shared\Observers\AuditObserver;
use App\Domain\User\Events\UserRegisteredEvent;
use App\Domain\User\Listeners\SendWelcomeEmailListener;
use App\Domain\Wishlist\Models\Wishlist;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // User Events
        UserRegisteredEvent::class => [
            SendWelcomeEmailListener::class,
        ],

        // Product Events - Add listeners as needed
        ProductCreatedEvent::class => [
            // InvalidateCacheListener::class,
            // UpdateSearchIndexListener::class,
        ],

        ProductUpdatedEvent::class => [
            // InvalidateCacheListener::class,
            // UpdateSearchIndexListener::class,
        ],

        ProductDeletedEvent::class => [
            // InvalidateCacheListener::class,
            // RemoveFromSearchIndexListener::class,
        ],
    ];

    /**
     * The model observers for your application.
     *
     * All models with Auditable trait will be logged.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $observers = [
        // User & Auth
        User::class => [AuditObserver::class],

        // Orders
        Order::class => [AuditObserver::class],
        OrderItem::class => [AuditObserver::class],

        // Payments
        Payment::class => [AuditObserver::class],

        // Products & Categories
        Product::class => [AuditObserver::class],
        Category::class => [AuditObserver::class],

        // Wishlist
        Wishlist::class => [AuditObserver::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
