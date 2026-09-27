<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\File;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $site = SiteSetting::current();
        $urls = [
            [$site->absolute('/'), null],
            [$site->absolute('/privacy'), $this->mtime(resource_path('views/legal/privacy.blade.php'))],
            [$site->absolute('/terms'), $this->mtime(resource_path('views/legal/terms.blade.php'))],
            [$site->absolute('/guidelines'), $this->mtime(resource_path('views/legal/guidelines.blade.php'))],
            [$site->absolute('/blog'), null],
            [$site->absolute('/guides'), null],
        ];

        Content::query()->public()->where('robots_index', true)->orderBy('id')->each(function (Content $content) use (&$urls, $site) {
            $urls[] = [$site->absolute($content->publicPath()), optional($content->updated_at)->toAtomString()];
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        foreach ($urls as [$loc, $lastmod]) {
            $xml .= '<url><loc>'.e($loc).'</loc>';
            if ($lastmod) {
                $xml .= '<lastmod>'.e($lastmod).'</lastmod>';
            }
            $xml .= '</url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    private function mtime(string $path): ?string
    {
        return File::exists($path) ? date(DATE_ATOM, File::lastModified($path)) : null;
    }
}
