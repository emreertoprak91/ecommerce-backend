<?php

use App\Exceptions\Handler;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware'leri
        $middleware->api(prepend: [
            ForceJsonResponse::class,  // Her zaman JSON response
            SetLocale::class,          // Dil desteği
        ]);

        // İsteğe bağlı: Request loglama (performans için production'da kapatılabilir)
        // $middleware->api(append: [
        //     \App\Http\Middleware\LogHttpRequest::class,
        // ]);

        // API isteklerinde login sayfasına redirect yerine 401 JSON dön
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel'ın default loglamasını devre dışı bırak
        // Tüm loglama Handler.php'de yapılacak
        $exceptions->dontReport([
            Throwable::class,
        ]);

        // Tüm exception handling'i Handler.php'ye delege et
        $exceptions->render(function (Throwable $e, $request) {
            $handler = new Handler(app());
            return $handler->render($request, $e);
        });
    })->create();
