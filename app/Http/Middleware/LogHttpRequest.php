<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log HTTP Request Middleware
 *
 * Tüm API isteklerini ve response'larını loglar.
 * Debug ve monitoring için kullanışlıdır.
 *
 * Loglanan bilgiler:
 * - Request: method, url, headers, body
 * - Response: status code, duration
 * - User: authenticated user id
 * - Trace ID: request takibi için unique id
 */
final class LogHttpRequest
{
    /**
     * Loglanmaması gereken hassas alanlar.
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'credit_card',
        'cvv',
        'card_number',
        'token',
        'secret',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Trace ID oluştur veya mevcut olanı kullan
        $traceId = $request->header('X-Trace-Id', Str::uuid()->toString());
        $request->headers->set('X-Trace-Id', $traceId);

        $startTime = microtime(true);

        // Request'i logla
        $this->logRequest($request, $traceId);

        /** @var Response $response */
        $response = $next($request);

        // Response'u logla
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $this->logResponse($request, $response, $traceId, $duration);

        // Trace ID'yi response header'a ekle
        $response->headers->set('X-Trace-Id', $traceId);

        return $response;
    }

    /**
     * Request bilgilerini logla.
     */
    private function logRequest(Request $request, string $traceId): void
    {
        $context = [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => Str::limit($request->userAgent() ?? '', 100),
        ];

        // Debug modda request body'yi de logla (hassas alanlar maskelenerek)
        if (config('app.debug')) {
            $context['body'] = $this->maskSensitiveData($request->all());
        }

        Log::channel('syslog')->debug('[HTTP Request]', $context);
    }

    /**
     * Response bilgilerini logla.
     */
    private function logResponse(Request $request, Response $response, string $traceId, float $duration): void
    {
        $statusCode = $response->getStatusCode();
        $level = $this->getLogLevel($statusCode);

        $context = [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'url' => $request->path(),
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
        ];

        $message = sprintf(
            '[HTTP Response] %s %s -> %d (%sms)',
            $request->method(),
            $request->path(),
            $statusCode,
            $duration
        );

        Log::channel('syslog')->{$level}($message, $context);
    }

    /**
     * Status code'a göre log level belirle.
     */
    private function getLogLevel(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            default => 'info',
        };
    }

    /**
     * Hassas verileri maskele.
     */
    private function maskSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif ($this->isSensitiveField($key)) {
                $data[$key] = '***MASKED***';
            }
        }

        return $data;
    }

    /**
     * Hassas alan mı kontrol et.
     */
    private function isSensitiveField(string $field): bool
    {
        $field = strtolower($field);

        foreach (self::SENSITIVE_FIELDS as $sensitiveField) {
            if (str_contains($field, $sensitiveField)) {
                return true;
            }
        }

        return false;
    }
}
