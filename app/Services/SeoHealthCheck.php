<?php

namespace App\Services;

use App\Http\Controllers\ContentController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Models\Content;
use App\Models\SeoRedirect;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Throwable;

class SeoHealthCheck
{
    public function __construct(private SeoBuilder $seo) {}

    public function findings(): array
    {
        $findings = [];
        $host = (string) parse_url(SiteSetting::current()->absolute('/'), PHP_URL_HOST);

        foreach ([
            '/', '/privacy', '/terms', '/guidelines', '/blog', '/guides', '/about', '/safety', '/contact',
            '/random-video-chat', '/talk-to-strangers', '/omegle-alternative', '/features', '/help',
        ] as $path) {
            $html = $this->render($path);
            if ($html === null) {
                $findings[] = $this->issue('error', $path, 'The page did not render.', 'Publish the page and confirm the route returns HTML.');
                continue;
            }
            if (substr_count(strtolower($html), '<h1') !== 1) {
                $findings[] = $this->issue('error', $path, 'The page does not have exactly one H1.', 'Keep a single H1 that matches the page intent.');
            }
            if (! preg_match('/name="description" content="[^"]+"/i', $html)) {
                $findings[] = $this->issue('error', $path, 'The meta description is empty.', 'Add a description that matches the visible page.');
            }
            if (! str_contains($html, 'rel="canonical"') || ($host !== '' && ! str_contains($html, $host))) {
                $findings[] = $this->issue('error', $path, 'The canonical URL is missing the configured host.', 'Set the canonical base to the public https origin.');
            }
            if (str_contains($html, 'noindex')) {
                $findings[] = $this->issue('error', $path, 'A public page contains noindex.', 'Leave indexing on for this URL.');
            }
        }

        $login = $this->render('/login');
        if ($login === null || ! str_contains($login, 'noindex')) {
            $findings[] = $this->issue('error', '/login', 'The login page is missing noindex.', 'Keep noindex on the sign-in page.');
        }

        try {
            $xml = app(SitemapController::class)()->getContent();
            if (! is_string($xml) || simplexml_load_string($xml) === false) {
                $findings[] = $this->issue('error', '/sitemap.xml', 'The sitemap is not valid XML.', 'Fix the sitemap response before submitting it.');
            } else {
                preg_match_all('#<loc>(.*?)</loc>#', $xml, $locs);
                foreach ($locs[1] ?? [] as $loc) {
                    $loc = html_entity_decode($loc);
                    if (! str_starts_with($loc, 'http')) {
                        $findings[] = $this->issue('error', '/sitemap.xml', 'A sitemap URL is not absolute: '.$loc, 'Emit absolute URLs from the canonical base.');
                    }
                }
                foreach (['/video', '/admin', '/login', '/home'] as $private) {
                    if (str_contains($xml, $private)) {
                        $findings[] = $this->issue('error', '/sitemap.xml', $private.' appears in the sitemap.', 'Keep private and account URLs out of the sitemap.');
                    }
                }
            }
        } catch (Throwable) {
            $findings[] = $this->issue('error', '/sitemap.xml', 'The sitemap could not be built.', 'Check the content tables and the canonical base.');
        }

        try {
            $robots = app(RobotsController::class)()->getContent();
            foreach (['Allow: /', 'Disallow: /admin', 'Disallow: /video', 'Sitemap:'] as $line) {
                if (! is_string($robots) || ! str_contains($robots, $line)) {
                    $findings[] = $this->issue('error', '/robots.txt', 'Missing "'.$line.'".', 'Restore the public allow rule, the private disallows, and the sitemap line.');
                }
            }
        } catch (Throwable) {
            $findings[] = $this->issue('error', '/robots.txt', 'robots.txt could not be built.', 'Check site settings before changing crawler rules.');
        }

        try {
            $dupes = Content::query()->pluck('title')->countBy()->filter(fn ($count) => $count > 1);
            foreach ($dupes as $title => $count) {
                $findings[] = $this->issue('warning', '/admin/content', 'Title "'.$title.'" is used '.$count.' times.', 'Give each published page its own title.');
            }
        } catch (Throwable) {
            $findings[] = $this->issue('warning', '/admin/content', 'Published titles could not be checked.', 'Run migrations so the content tables exist.');
        }

        $home = $this->render('/');
        if ($home !== null && preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $home, $match)) {
            if (json_decode($match[1], true) === null) {
                $findings[] = $this->issue('error', '/', 'Homepage structured data is not valid JSON.', 'Remove unsupported properties from the generated graph.');
            }
        } elseif ($home !== null) {
            $findings[] = $this->issue('error', '/', 'Homepage structured data is missing.', 'Keep the Organization and WebSite graph on the homepage.');
        }

        try {
            foreach (SeoRedirect::query()->where('enabled', true)->get() as $redirect) {
                if ($redirect->to_path && $redirect->from_path === $redirect->to_path) {
                    $findings[] = $this->issue('error', $redirect->from_path, 'This redirect points at itself.', 'Delete the redirect or send it to a different path.');
                }
            }
        } catch (Throwable) {
            // Redirects are absent until the SEO tables exist.
        }

        return $findings;
    }

    private function render(string $path): ?string
    {
        try {
            $view = match ($path) {
                '/' => view('landing', ['seo' => $this->seo->home()]),
                '/privacy' => view('legal.privacy', ['seo' => $this->seo->page('Privacy', 'How Koko Meet handles account data, live video, reports, and moderation records.', '/privacy')]),
                '/terms' => view('legal.terms', ['seo' => $this->seo->page('Terms', 'The rules for using Koko Meet, including conduct, account suspension, and call availability.', '/terms')]),
                '/guidelines' => view('legal.guidelines', ['seo' => $this->seo->page('Community Guidelines', 'What is not allowed on Koko Meet and how to report or block someone during a call.', '/guidelines')]),
                '/blog' => app(ContentController::class)->blog(Request::create('/blog', 'GET')),
                '/guides' => app(ContentController::class)->guides(Request::create('/guides', 'GET')),
                '/login' => view('auth.login'),
                default => app(ContentController::class)->page(ltrim($path, '/')),
            };
            if (! is_object($view) || ! method_exists($view, 'render')) {
                return null;
            }

            return $view->render();
        } catch (Throwable) {
            return null;
        }
    }

    private function issue(string $severity, string $url, string $description, string $fix): array
    {
        return compact('severity', 'url', 'description', 'fix');
    }
}
