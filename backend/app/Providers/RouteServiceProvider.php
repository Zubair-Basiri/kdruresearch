<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            $this->loadRoutesFrom(base_path('routes/web.php'));
            $this->loadRoutesFrom(base_path('routes/api.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        // Define API rate limiter
        RateLimiter::for('api', function ($request) {
            $key = optional($request->user())->id ?: $request->ip();
            return Limit::perMinute(100)->by($key);
        });
    }
}
