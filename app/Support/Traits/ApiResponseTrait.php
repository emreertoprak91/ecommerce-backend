<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * API Response Trait
 *
 * Tüm API response'ları için standart format sağlar.
 * SysLog entegrasyonu ile tüm response'ları loglar.
 */
trait ApiResponseTrait
{
    /**
     * Başarılı response döndür.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => now()->toISOString(),
                'trace_id' => request()->header('X-Trace-Id', uniqid('trace_', true)),
            ], $meta),
        ];

        $this->logToSysLog('info', $message, [
            'status_code' => $statusCode,
            'trace_id' => $response['meta']['trace_id'],
        ]);

        return response()->json($response, $statusCode);
    }

    /**
     * Oluşturma başarılı response (201).
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Silme başarılı response (204 veya 200).
     */
    protected function deletedResponse(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->successResponse(null, $message, 200);
    }

    /**
     * Sayfalama ile response döndür.
     */
    protected function paginatedResponse(
        mixed $paginator,
        string $resourceClass,
        string $message = 'Success'
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $resourceClass::collection($paginator->items()),
            'meta' => [
                'timestamp' => now()->toISOString(),
                'trace_id' => request()->header('X-Trace-Id', uniqid('trace_', true)),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        $this->logToSysLog('info', $message, [
            'status_code' => 200,
            'total_items' => $paginator->total(),
            'trace_id' => $response['meta']['trace_id'],
        ]);

        return response()->json($response, 200);
    }

    /**
     * Hata response döndür.
     */
    protected function errorResponse(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        ?Throwable $exception = null
    ): JsonResponse {
        $traceId = request()->header('X-Trace-Id', uniqid('trace_', true));

        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'trace_id' => $traceId,
            ],
        ];

        // Debug modunda exception detaylarını ekle
        if (config('app.debug') && $exception) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
            ];
        }

        $this->logToSysLog('error', $message, [
            'status_code' => $statusCode,
            'errors' => $errors,
            'trace_id' => $traceId,
            'exception' => $exception ? get_class($exception) : null,
            'exception_message' => $exception?->getMessage(),
        ]);

        return response()->json($response, $statusCode);
    }

    /**
     * Validation hatası response (422).
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Not Found response (404).
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Unauthorized response (401).
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden response (403).
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Server Error response (500).
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error',
        ?Throwable $exception = null
    ): JsonResponse {
        return $this->errorResponse($message, 500, null, $exception);
    }

    /**
     * SysLog'a log yaz.
     */
    private function logToSysLog(string $level, string $message, array $context = []): void
    {
        $context = array_merge($context, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => Auth::id(),
        ]);

        Log::channel('syslog')->$level("[API] {$message}", $context);
    }
}
