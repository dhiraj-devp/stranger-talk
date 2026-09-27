<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;

class ManifestController extends Controller
{
    public function __invoke()
    {
        $site = SiteSetting::current();
        $icon = $site->assetAbsolute($site->web_app_icon) ?: $site->faviconUrl();
        $body = [
            'name' => $site->site_name,
            'short_name' => $site->site_name,
            'description' => $site->tagline,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#07060b',
            'theme_color' => $site->theme_color ?: '#7c3aed',
            'lang' => $site->language ?: 'en',
            'icons' => [[
                'src' => $icon,
                'sizes' => str_ends_with(parse_url($icon, PHP_URL_PATH) ?: '', '.svg') ? 'any' : '192x192',
                'type' => str_ends_with(parse_url($icon, PHP_URL_PATH) ?: '', '.svg') ? 'image/svg+xml' : 'image/png',
                'purpose' => 'any',
            ]],
        ];

        return response()->json($body, 200, ['Cache-Control' => 'public, max-age=300']);
    }
}
