<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
     * Every visitor message costs an LLM round trip, so it is limited per
     * conversation and, as a backstop against rotating tokens, per address.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('advisor-start', fn (Request $request) => Limit::perMinute(10)->by('start:'.$request->ip()));

        RateLimiter::for('advisor-message', fn (Request $request) => [
            Limit::perMinute(12)->by('conversation:'.($request->input('visitor_token') ?: $request->ip())),
            Limit::perMinute(40)->by('ip:'.$request->ip()),
        ]);

        RateLimiter::for('advisor-history', fn (Request $request) => Limit::perMinute(60)->by('history:'.$request->ip()));
    }
}
