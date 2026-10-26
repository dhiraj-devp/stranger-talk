<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        try {
            $key = (string) $request->cookie('koko_vid', '');
            if (! preg_match('/^[A-Za-z0-9]{32}$/', $key)) {
                $key = Str::random(32);
                cookie()->queue(cookie('koko_vid', $key, 60 * 24, '/', null, $request->isSecure(), true, false, 'lax'));
            }

            $visit = Visit::query()->firstOrCreate(
                ['key' => $key],
                [
                    'first_path' => $this->path($request),
                    'referrer_host' => $this->referrer($request),
                    'device' => $this->device($request),
                    'country' => $this->country($request),
                    'started_at' => now(),
                ]
            );

            PageView::query()->create([
                'visit_id' => $visit->id,
                'path' => $this->path($request),
                'duration' => 0,
                'entered_at' => now(),
            ]);
        } catch (\Throwable) {
            return $response;
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->expectsJson() || $response->getStatusCode() >= 400) {
            return false;
        }

        return ! $request->is('admin', 'admin/*', 'match/*', 'up', 'webrtc.js', 'analytics/*', 'sitemap.xml', 'robots.txt');
    }

    private function path(Request $request): string
    {
        return mb_substr('/'.ltrim($request->path(), '/'), 0, 190);
    }

    private function referrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return is_string($host) && $host !== '' && $host !== $request->getHost()
            ? mb_substr($host, 0, 190)
            : null;
    }

    private function device(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());
        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'tablet';
        }
        if (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function country(Request $request): ?string
    {
        $code = strtoupper(substr((string) $request->headers->get('CF-IPCountry'), 0, 2));

        return preg_match('/^[A-Z]{2}$/', $code) ? $code : null;
    }
}
