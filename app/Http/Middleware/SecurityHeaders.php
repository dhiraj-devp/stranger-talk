<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $script = "script-src 'self'";
        $connect = "connect-src 'self' ws: wss: https:";
        if (app()->environment('production')) {
            $site = SiteSetting::current();
            if ($site->ga_measurement_id || $site->gtm_id) {
                $script = "script-src 'self' https://www.googletagmanager.com https://www.google-analytics.com";
                $connect = "connect-src 'self' ws: wss: https: https://www.google-analytics.com https://www.googletagmanager.com";
            }
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=()');
        $first = explode('/', trim($request->getPathInfo(), '/'))[0] ?? '';
        if (in_array($first, ['home', 'video', 'profile', 'admin', 'match', 'blocks', 'banned', 'auth', 'api', 'login', 'health', 'up'], true)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            $script,
            "style-src 'self'",
            "img-src 'self' https: data:",
            "media-src 'self' blob:",
            $connect,
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
