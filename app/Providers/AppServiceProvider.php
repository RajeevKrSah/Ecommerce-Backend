<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

class AppServiceProvider extends ServiceProvider
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
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Use cache-based rate limiting instead of Redis
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json(['message' => 'Too many requests'], 429);
            });
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json(['message' => 'Too many login attempts'], 429);
            });
        });

        RateLimiter::for('admin_login', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())->response(function () {
                return response()->json(['message' => 'Too many admin login attempts'], 429);
            });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())->response(function () {
                return response()->json(['message' => 'Too many registration attempts'], 429);
            });
        });
    }
}
