<?php

namespace App\Http\Middleware;

use App\Models\SeoRedirect;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo() ?: '/';
        $normalized = $this->normalize($path);
        if ($normalized !== $path) {
            return redirect($normalized.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
        }

        if (app()->environment('production')) {
            $base = SiteSetting::current()->canonical_base;
            $parts = $base ? parse_url($base) : false;
            $host = is_array($parts) ? ($parts['host'] ?? null) : null;
            $scheme = is_array($parts) ? ($parts['scheme'] ?? null) : null;
            if ($host && $scheme && ($request->getHost() !== $host || ($scheme === 'https' && ! $request->secure()))) {
                return redirect()->away($scheme.'://'.$host.$request->getRequestUri(), 301);
            }
        }

        $redirect = SeoRedirect::map()->get($normalized);
        if ($redirect) {
            if ((int) $redirect->status_code === 410) {
                return response()->view('errors.410', [], 410);
            }
            if ($redirect->to_path && $redirect->to_path !== $normalized) {
                return redirect($redirect->to_path, in_array((int) $redirect->status_code, [301, 302], true) ? (int) $redirect->status_code : 301);
            }
        }

        return $next($request);
    }

    private function normalize(string $path): string
    {
        $clean = mb_strtolower($path);
        if ($clean !== '/') {
            $clean = rtrim($clean, '/') ?: '/';
        }

        return $clean;
    }
}
