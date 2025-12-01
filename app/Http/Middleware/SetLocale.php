<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set Locale Middleware
 *
 * Request'teki Accept-Language header'ına göre uygulama dilini ayarlar.
 * Desteklenen diller: tr, en
 *
 * Öncelik sırası:
 * 1. Query parameter: ?lang=tr
 * 2. Accept-Language header
 * 3. Default: config('app.locale')
 */
final class SetLocale
{
    /**
     * Desteklenen diller.
     */
    private const SUPPORTED_LOCALES = ['tr', 'en'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        app()->setLocale($locale);

        /** @var Response $response */
        $response = $next($request);

        // Response'a Content-Language header'ı ekle
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    /**
     * Request'ten locale belirle.
     */
    private function determineLocale(Request $request): string
    {
        // 1. Query parameter kontrolü
        $queryLocale = $request->query('lang');
        if ($queryLocale && $this->isSupported($queryLocale)) {
            return $queryLocale;
        }

        // 2. Accept-Language header kontrolü
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale && $this->isSupported($locale)) {
                return $locale;
            }
        }

        // 3. Default locale
        return config('app.locale', 'en');
    }

    /**
     * Accept-Language header'ını parse et.
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        // "tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7" formatını parse et
        $languages = explode(',', $header);

        foreach ($languages as $language) {
            $parts = explode(';', trim($language));
            $locale = trim($parts[0]);

            // "tr-TR" -> "tr" formatına çevir
            $shortLocale = explode('-', $locale)[0];

            if ($this->isSupported($shortLocale)) {
                return $shortLocale;
            }
        }

        return null;
    }

    /**
     * Locale destekleniyor mu?
     */
    private function isSupported(string $locale): bool
    {
        return in_array(strtolower($locale), self::SUPPORTED_LOCALES, true);
    }
}
