<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotBanned;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SeoRedirects;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'active' => EnsureNotBanned::class,
            'profile' => EnsureProfileComplete::class,
        ]);
        $middleware->append(SeoRedirects::class);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson() || config('app.debug')) {
                return null;
            }
            if ($e instanceof ValidationException || $e instanceof AuthenticationException || $e instanceof HttpExceptionInterface) {
                return null;
            }

            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        });
    })->create();
