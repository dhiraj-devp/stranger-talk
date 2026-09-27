<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;

class RobotsController extends Controller
{
    public function __invoke()
    {
        $site = SiteSetting::current();
        $lines = [
            'User-agent: *',
            $site->robots_disallow_all ? 'Disallow: /' : 'Allow: /',
        ];
        foreach ([
            '/home', '/video', '/profile', '/admin', '/match', '/blocks', '/banned', '/auth/', '/api/', '/login', '/health', '/up',
        ] as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        foreach (preg_split("/\r\n|\n|\r/", (string) $site->robots_extra) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('#^Disallow:\s+/\S+#', $line) && ! preg_match('#^Disallow:\s+/\s*$#', $line)) {
                $lines[] = $line;
            }
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.$site->absolute('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
