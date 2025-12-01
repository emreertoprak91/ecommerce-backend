<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force JSON Response Middleware
 *
 * API isteklerinde her zaman JSON response döndürülmesini sağlar.
 * Bu middleware sayesinde:
 * - Accept header'ı otomatik olarak application/json yapılır
 * - Exception handler her zaman JSON formatında response döner
 * - HTML error sayfaları yerine JSON error response'ları döner
 */
final class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // API isteklerinde Accept header'ı JSON olarak ayarla
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
