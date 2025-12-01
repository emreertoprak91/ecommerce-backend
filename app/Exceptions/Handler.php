<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\Traits\ApiResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * Global Exception Handler
 *
 * Tüm exception'ları yakalar ve standart API response formatında döner.
 * SysLog entegrasyonu ile tüm hataları loglar.
 */
class Handler extends ExceptionHandler
{
    use ApiResponseTrait;

    /**
     * Exception'ları loglarken kullanılacak log seviyeleri.
     * INFO: Beklenen business logic hataları (404, 401, 403, 422)
     * WARNING: Potansiyel sorunlar (429 rate limit)
     * ERROR: Beklenmeyen hatalar (500)
     * CRITICAL: Sistem hataları (Database, Memory)
     */

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * Bu metod bootstrap/app.php'den çağrılır.
     * API istekleri için özel JSON response döner.
     */
    public function render($request, Throwable $e): Response|JsonResponse
    {
        // Tüm exception'ları logla (uygun seviyede)
        $this->logException($e, $request);

        // API istekleri için JSON response
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($e, $request);
        }

        // Web istekleri için default handler
        return parent::render($request, $e);
    }

    /**
     * Exception için uygun log seviyesini belirle.
     *
     * @return string 'info'|'warning'|'error'|'critical'
     */
    protected function getLogLevel(Throwable $e): string
    {
        // CRITICAL: Sistem/Database hataları
        if ($e instanceof \PDOException
            || $e instanceof QueryException
            || $e instanceof \ErrorException
        ) {
            return 'critical';
        }

        // WARNING: Rate limiting, potansiyel kötüye kullanım
        if ($e instanceof TooManyRequestsHttpException) {
            return 'warning';
        }

        // INFO: Beklenen business logic hataları
        if ($e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException
            || $e instanceof ModelNotFoundException
            || $e instanceof NotFoundHttpException
            || $e instanceof MethodNotAllowedHttpException
            || $e instanceof \App\Domain\Shared\Exceptions\DomainException
        ) {
            return 'info';
        }

        // ERROR: Diğer tüm beklenmeyen hatalar
        return 'error';
    }

    /**
     * Handle unauthenticated users.
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return $this->unauthorizedResponse('Unauthenticated. Please login to continue.');
    }

    /**
     * API exception'larını handle et.
     *
     * Tüm exception tipleri burada merkezi olarak yönetilir.
     * Controller'larda ValidationException için özel catch bloğu gerekli değil.
     */
    protected function handleApiException(Throwable $exception, Request $request): JsonResponse
    {
        // 1. Validation Exception (422)
        if ($exception instanceof ValidationException) {
            return $this->validationErrorResponse(
                $exception->errors(),
                $exception->getMessage()
            );
        }

        // 2. Authentication Exception (401)
        if ($exception instanceof AuthenticationException) {
            return $this->unauthorizedResponse('Unauthenticated. Please login to continue.');
        }

        // 3. Authorization Exception (403)
        if ($exception instanceof AuthorizationException) {
            return $this->forbiddenResponse(
                $exception->getMessage() ?: 'You are not authorized to perform this action.'
            );
        }

        // 4. Unauthorized HTTP Exception (401)
        if ($exception instanceof UnauthorizedHttpException) {
            return $this->unauthorizedResponse(
                $exception->getMessage() ?: 'Unauthorized.'
            );
        }

        // 5. Model Not Found Exception (404)
        if ($exception instanceof ModelNotFoundException) {
            $model = class_basename($exception->getModel());
            return $this->notFoundResponse("{$model} not found.");
        }

        // 6. Not Found HTTP Exception (404)
        if ($exception instanceof NotFoundHttpException) {
            return $this->notFoundResponse(
                $exception->getMessage() ?: 'The requested resource was not found.'
            );
        }

        // 7. Bad Request HTTP Exception (400)
        if ($exception instanceof BadRequestHttpException) {
            return $this->errorResponse(
                $exception->getMessage() ?: 'Bad request.',
                Response::HTTP_BAD_REQUEST
            );
        }

        // 8. Method Not Allowed HTTP Exception (405)
        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse(
                'Method not allowed for this endpoint.',
                Response::HTTP_METHOD_NOT_ALLOWED
            );
        }

        // 9. Too Many Requests HTTP Exception (429)
        if ($exception instanceof TooManyRequestsHttpException) {
            return $this->errorResponse(
                'Too many requests. Please slow down.',
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        // 10. Generic HTTP Exception (4xx, 5xx)
        if ($exception instanceof HttpException) {
            return $this->errorResponse(
                $exception->getMessage() ?: 'HTTP Error',
                $exception->getStatusCode(),
                null,
                $exception
            );
        }

        // 11. Domain Validation Exceptions (field-specific errors like duplicate SKU, email)
        if ($exception instanceof \App\Domain\Product\Exceptions\DuplicateSkuException) {
            return $this->validationErrorResponse(
                ['sku' => [$exception->getMessage()]],
                'Validation failed'
            );
        }

        // 12. Auth Domain Exceptions
        if ($exception instanceof \App\Domain\User\Exceptions\EmailAlreadyExistsException) {
            return $this->validationErrorResponse(
                ['email' => [$exception->getMessage()]],
                'Validation failed'
            );
        }

        if ($exception instanceof \App\Domain\User\Exceptions\InvalidCredentialsException) {
            return $this->validationErrorResponse(
                ['email' => [$exception->getMessage()]],
                'Invalid credentials'
            );
        }

        if ($exception instanceof \App\Domain\User\Exceptions\EmailNotVerifiedException) {
            return $this->errorResponse(
                'Please verify your email address before logging in. Check your inbox for the verification link.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'email_verified' => false,
                    'email' => $exception->email,
                ]
            );
        }

        // 13. Domain-specific exceptions (custom business logic exceptions)
        if ($exception instanceof \App\Domain\Shared\Exceptions\DomainException) {
            return $this->errorResponse(
                $exception->getMessage(),
                $exception->getCode() ?: Response::HTTP_BAD_REQUEST,
                null,
                $exception
            );
        }

        // 12. Database Query Exception (500)
        if ($exception instanceof QueryException) {
            Log::channel('syslog')->critical('[DATABASE] Query exception', [
                'trace_id' => $request->header('X-Trace-Id'),
                'sql' => $exception->getSql(),
                'bindings' => $exception->getBindings(),
            ]);

            return $this->serverErrorResponse(
                config('app.debug') ? $exception->getMessage() : 'A database error occurred.',
                $exception
            );
        }

        // 13. Generic server error (500)
        return $this->serverErrorResponse(
            config('app.debug') ? $exception->getMessage() : 'An unexpected error occurred.',
            $exception
        );
    }

    /**
     * Exception'ı uygun seviyede logla.
     *
     * Log Seviyeleri:
     * - INFO: 404 Not Found, 401 Unauthorized, 403 Forbidden, 422 Validation
     * - WARNING: 429 Too Many Requests
     * - ERROR: 500 Internal Server Error (beklenmeyen hatalar)
     * - CRITICAL: Database, Memory, System hataları
     */
    protected function logException(Throwable $exception, $request = null): void
    {
        $request = $request ?? request();
        $level = $this->getLogLevel($exception);
        $httpCode = $this->getHttpStatusCode($exception);

        $context = [
            'exception' => get_class($exception),
            'http_code' => $httpCode,
            'message' => $exception->getMessage(),
            'trace_id' => $request->header('X-Trace-Id', uniqid('trace_', true)),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
        ];

        // ERROR ve CRITICAL için detaylı bilgi ekle
        if (in_array($level, ['error', 'critical'])) {
            $context['file'] = $exception->getFile();
            $context['line'] = $exception->getLine();
            $context['user_agent'] = $request->userAgent();
        }

        // DomainException için context ekle
        if ($exception instanceof \App\Domain\Shared\Exceptions\DomainException) {
            $context['domain_context'] = $exception->getContext();
        }

        $logMessage = "[{$httpCode}] " . $exception->getMessage();

        // Production'da syslog, development/test'te default channel
        $channel = app()->environment('production') ? 'syslog' : null;
        Log::channel($channel)->{$level}($logMessage, $context);
    }

    /**
     * Exception için HTTP status code belirle.
     */
    protected function getHttpStatusCode(Throwable $e): int
    {
        if ($e instanceof ValidationException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY; // 422
        }
        if ($e instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED; // 401
        }
        if ($e instanceof AuthorizationException) {
            return Response::HTTP_FORBIDDEN; // 403
        }
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return Response::HTTP_NOT_FOUND; // 404
        }
        if ($e instanceof MethodNotAllowedHttpException) {
            return Response::HTTP_METHOD_NOT_ALLOWED; // 405
        }
        if ($e instanceof TooManyRequestsHttpException) {
            return Response::HTTP_TOO_MANY_REQUESTS; // 429
        }
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        if ($e instanceof \App\Domain\Shared\Exceptions\DomainException) {
            return $e->getCode() ?: Response::HTTP_BAD_REQUEST;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR; // 500
    }
}
