<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

final class HealthCheckServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only register health checks in non-testing environments
        if (app()->environment('testing')) {
            return;
        }

        Health::checks([
            // Database connectivity check
            DatabaseCheck::new()
                ->name('MySQL Database')
                ->connectionName(config('database.default')),

            // Redis connectivity check
            RedisCheck::new()
                ->name('Redis Cache')
                ->connectionName('default'),

            // Cache driver check
            CacheCheck::new()
                ->name('Cache Store'),

            // Used disk space check (warn at 70%, fail at 90%)
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            // Environment check - ensure we're running in correct environment
            EnvironmentCheck::new()
                ->expectEnvironment(app()->environment()),

            // Debug mode check - warn if debug is on in production
            DebugModeCheck::new()
                ->name('Debug Mode'),
        ]);
    }
}
