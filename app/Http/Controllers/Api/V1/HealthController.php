<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Health Check Controller
 *
 * Provides health check endpoints for monitoring and load balancers.
 *
 * @OA\Tag(
 *     name="Health",
 *     description="Health check endpoints"
 * )
 */
final class HealthController extends Controller
{
    /**
     * Basic health check.
     *
     * @OA\Get(
     *     path="/health",
     *     summary="Basic health check",
     *     tags={"Health"},
     *     @OA\Response(
     *         response=200,
     *         description="Service is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="healthy"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Detailed health check with all services.
     *
     * @OA\Get(
     *     path="/health/detailed",
     *     summary="Detailed health check",
     *     tags={"Health"},
     *     @OA\Response(
     *         response=200,
     *         description="Detailed health status"
     *     )
     * )
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $isHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
        ], $isHealthy ? 200 : 503);
    }

    /**
     * Readiness check for Kubernetes.
     */
    public function ready(): JsonResponse
    {
        $dbHealthy = $this->checkDatabase()['status'] === 'healthy';
        $redisHealthy = $this->checkRedis()['status'] === 'healthy';

        if ($dbHealthy && $redisHealthy) {
            return response()->json(['status' => 'ready']);
        }

        return response()->json(['status' => 'not ready'], 503);
    }

    /**
     * Liveness check for Kubernetes.
     */
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'alive']);
    }

    private function checkApp(): array
    {
        return [
            'status' => 'healthy',
            'details' => [
                'name' => config('app.name'),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'details' => [
                    'connection' => config('database.default'),
                    'latency_ms' => $latency,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'details' => [
                    'connection' => config('database.redis.default.host'),
                    'latency_ms' => $latency,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'details' => [
                    'driver' => config('cache.default'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = storage_path('app/.health_check');
            file_put_contents($path, 'test');
            $readable = file_get_contents($path) === 'test';
            unlink($path);

            return [
                'status' => $readable ? 'healthy' : 'unhealthy',
                'details' => [
                    'disk' => config('filesystems.default'),
                    'writable' => $readable,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
