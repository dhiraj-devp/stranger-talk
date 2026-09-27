<?php

namespace App\Providers;

use App\Models\Report;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        View::composer('layouts.admin', function ($view) {
            $view->with('pendingReports', Report::query()->where('status', Report::PENDING)->count());
        });

        foreach (['login', 'register', 'password' => 'password_reset', 'matchmaking', 'next', 'reports', 'blocks', 'signal', 'google', 'api' => 'signal'] as $name => $key) {
            if (is_int($name)) {
                $name = $key;
            }
            RateLimiter::for($name, function (Request $request) use ($key, $name) {
                $bucket = $request->user()?->id ?: strtolower((string) $request->input('email')).'|'.$request->ip();

                return Limit::perMinute(max(1, (int) config('platform.limits.'.$key, 60)))->by($name.$bucket);
            });
        }
    }
}
