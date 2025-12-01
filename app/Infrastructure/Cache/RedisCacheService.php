<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Redis Cache Service
 *
 * High-performance caching layer for frequently accessed data.
 * Uses Redis for fast key-value storage.
 */
final class RedisCacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Cache prefixes for different data types.
     */
    private const PREFIXES = [
        'product' => 'product:',
        'products' => 'products:',
        'category' => 'category:',
        'categories' => 'categories:',
        'user' => 'user:',
        'config' => 'config:',
    ];

    /**
     * Get cached value or execute callback and cache result.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;

        return Cache::remember($key, $ttl, function () use ($callback, $key) {
            Log::debug("Cache miss for key: {$key}");

            return $callback();
        });
    }

    /**
     * Get cached value or execute callback and cache forever.
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return Cache::rememberForever($key, function () use ($callback, $key) {
            Log::debug("Cache miss (forever) for key: {$key}");

            return $callback();
        });
    }

    /**
     * Store value in cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;

        return Cache::put($key, $value, $ttl);
    }

    /**
     * Store value in cache forever.
     */
    public function forever(string $key, mixed $value): bool
    {
        return Cache::forever($key, $value);
    }

    /**
     * Get value from cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Check if key exists in cache.
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Remove key from cache.
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Remove multiple keys from cache.
     */
    public function forgetMany(array $keys): void
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }
    }

    /**
     * Clear all cache with a specific prefix.
     */
    public function forgetByPrefix(string $prefix): void
    {
        // This requires Redis SCAN command for pattern matching
        $pattern = $prefix . '*';

        try {
            // Use Redis facade for SCAN to avoid KEYS and driver-specific methods
            // PhpRedis: scan(&$iterator, $pattern = null, $count = 0)
            $iterator = null;
            do {
                $keys = \Illuminate\Support\Facades\Redis::scan($iterator, $pattern, 100);
                if ($keys !== false && !empty($keys)) {
                    // PhpRedis 'del' accepts an array of keys
                    \Illuminate\Support\Facades\Redis::del($keys);
                }
            } while ($iterator !== null && $iterator !== 0 && $iterator !== '0');
        } catch (\Exception $e) {
            Log::error("Failed to clear cache by prefix: {$prefix}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Increment a cached value.
     */
    public function increment(string $key, int $value = 1): int
    {
        return Cache::increment($key, $value);
    }

    /**
     * Decrement a cached value.
     */
    public function decrement(string $key, int $value = 1): int
    {
        return Cache::decrement($key, $value);
    }

    /**
     * Add tags to a cache entry.
     */
    public function tags(array $tags): self
    {
        // Note: Redis supports tags through Cache::tags()
        return $this;
    }

    /**
     * Flush tagged cache entries.
     */
    public function flushTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to flush tags", ['tags' => $tags, 'error' => $e->getMessage()]);

            return false;
        }
    }

    // ==================== PRODUCT CACHE ====================

    /**
     * Get product cache key.
     */
    public function productKey(int|string $id): string
    {
        return self::PREFIXES['product'] . $id;
    }

    /**
     * Get products list cache key.
     */
    public function productsListKey(string $hash): string
    {
        return self::PREFIXES['products'] . 'list:' . $hash;
    }

    /**
     * Cache product.
     */
    public function cacheProduct(int $id, mixed $product, ?int $ttl = null): bool
    {
        return $this->put($this->productKey($id), $product, $ttl);
    }

    /**
     * Get cached product.
     */
    public function getProduct(int $id): mixed
    {
        return $this->get($this->productKey($id));
    }

    /**
     * Invalidate product cache.
     */
    public function invalidateProduct(int $id): bool
    {
        return $this->forget($this->productKey($id));
    }

    /**
     * Invalidate all products cache.
     */
    public function invalidateAllProducts(): void
    {
        $this->forgetByPrefix(self::PREFIXES['products']);
    }

    // ==================== CATEGORY CACHE ====================

    /**
     * Get category cache key.
     */
    public function categoryKey(int|string $id): string
    {
        return self::PREFIXES['category'] . $id;
    }

    /**
     * Cache category.
     */
    public function cacheCategory(int $id, mixed $category, ?int $ttl = null): bool
    {
        return $this->put($this->categoryKey($id), $category, $ttl);
    }

    /**
     * Get cached category.
     */
    public function getCategory(int $id): mixed
    {
        return $this->get($this->categoryKey($id));
    }

    /**
     * Cache all categories (for menu, etc.).
     */
    public function cacheAllCategories(mixed $categories, ?int $ttl = null): bool
    {
        return $this->put(self::PREFIXES['categories'] . 'all', $categories, $ttl);
    }

    /**
     * Get all cached categories.
     */
    public function getAllCategories(): mixed
    {
        return $this->get(self::PREFIXES['categories'] . 'all');
    }
}
