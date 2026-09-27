<?php

namespace App\Services;

use App\Models\Content;
use App\Models\SiteSetting;

class SeoBuilder
{
    public function home(): SeoMeta
    {
        $site = SiteSetting::current();
        $title = $site->default_title;
        $description = $site->default_description;
        $canonical = $site->absolute('/');
        $image = $site->assetAbsolute($site->og_image) ?: $site->assetAbsolute($site->logo);
        $graph = [
            $this->organization($site, $canonical),
            [
                '@type' => 'WebSite',
                'name' => $site->site_name,
                'url' => $canonical,
                'description' => $site->organization_description ?: $description,
            ],
            [
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $canonical,
                'description' => $description,
                'isPartOf' => ['@type' => 'WebSite', 'name' => $site->site_name, 'url' => $canonical],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $faq) => [
                    '@type' => 'Question',
                    'name' => $faq['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
                ], self::faqs($site->site_name)),
            ],
        ];

        return $this->meta($site, $title, $description, $canonical, 'index,follow', $image, $graph, [['Home', null]]);
    }

    public function page(string $title, string $description, string $path): SeoMeta
    {
        $site = SiteSetting::current();
        $canonical = $site->absolute($path);
        $crumbs = [['Home', $site->absolute('/')], [$title, null]];
        $graph = [
            [
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $canonical,
                'description' => $description,
            ],
            $this->breadcrumbs($site, $crumbs),
        ];

        return $this->meta($site, $title.' — '.$site->site_name, $description, $canonical, 'index,follow', $site->assetAbsolute($site->og_image), $graph, $crumbs);
    }

    public function content(Content $content, ?string $prev = null, ?string $next = null): SeoMeta
    {
        $site = SiteSetting::current();
        $title = $content->seo_title ?: $content->title;
        $description = $content->seo_description ?: ($content->excerpt ?: $site->default_description);
        $path = $content->canonical ?: $content->publicPath();
        $canonical = $site->absolute($path);
        $image = $site->assetAbsolute($content->og_image ?: $content->featured_image) ?: $site->assetAbsolute($site->og_image);
        $robots = ($content->robots_index ? 'index' : 'noindex').','.($content->robots_follow ? 'follow' : 'nofollow');
        $section = match ($content->type) {
            'post' => ['Blog', $site->absolute('/blog')],
            'guide' => ['Guides', $site->absolute('/guides')],
            default => null,
        };
        $crumbs = [['Home', $site->absolute('/')]];
        if ($section) {
            $crumbs[] = $section;
        }
        $crumbs[] = [$content->breadcrumb_title ?: $content->title, null];
        $graph = $content->json_ld ? null : [
            $this->contentNode($site, $content, $title, $description, $canonical, $image),
            $this->breadcrumbs($site, $crumbs),
        ];

        return $this->meta(
            $site,
            str_contains($title, $site->site_name) ? $title : $title.' — '.$site->site_name,
            $description,
            $canonical,
            $robots,
            $image,
            $graph ?? $this->customGraph($content->json_ld),
            $crumbs,
            $content->og_title,
            $content->og_description,
            $content->twitter_title,
            $content->twitter_description,
            $site->assetAbsolute($content->twitter_image ?: $content->og_image ?: $content->featured_image),
            $prev,
            $next,
        );
    }

    public function collection(string $name, string $description, string $path, ?string $prev, ?string $next): SeoMeta
    {
        $site = SiteSetting::current();
        $canonical = $site->absolute($path);
        $crumbs = [['Home', $site->absolute('/')], [$name, null]];

        return $this->meta($site, $name.' — '.$site->site_name, $description, $canonical, 'index,follow', $site->assetAbsolute($site->og_image), [
            ['@type' => 'CollectionPage', 'name' => $name, 'url' => $canonical, 'description' => $description],
            $this->breadcrumbs($site, $crumbs),
        ], $crumbs, null, null, null, null, null, $prev, $next);
    }

    public static function faqs(string $site): array
    {
        return [
            ['q' => 'Do you record video calls?', 'a' => 'No. Video and audio are not stored by '.$site.'.'],
            ['q' => 'How does matching work?', 'a' => 'You are paired at random with one other person who is also looking.'],
            ['q' => 'Can I skip someone?', 'a' => 'Yes. Next ends the current call and looks for someone else.'],
            ['q' => 'What happens if someone behaves badly?', 'a' => 'Leave the call, then report or block them from the menu.'],
            ['q' => 'Do I need an account?', 'a' => 'Yes. Sign in with Google, then confirm a short profile the first time.'],
        ];
    }

    private function contentNode(SiteSetting $site, Content $content, string $title, string $description, string $canonical, ?string $image): array
    {
        $type = $content->schema_type ?: match ($content->type) {
            'post', 'guide' => 'Article',
            'faq' => 'FAQPage',
            default => 'WebPage',
        };
        if ($type === 'FAQPage' && $content->faqPairs() === []) {
            $type = 'WebPage';
        }
        $node = [
            '@type' => $type,
            'headline' => $title,
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'mainEntityOfPage' => $canonical,
            'dateModified' => optional($content->updated_at)->toAtomString(),
            'publisher' => ['@type' => 'Organization', 'name' => $site->organization_name ?: $site->site_name],
        ];
        if ($content->published_at) {
            $node['datePublished'] = $content->published_at->toAtomString();
        }
        if ($image) {
            $node['image'] = $image;
        }
        if ($content->author) {
            $node['author'] = ['@type' => 'Person', 'name' => $content->author->name];
        } else {
            $node['author'] = ['@type' => 'Organization', 'name' => $site->organization_name ?: $site->site_name];
        }
        if ($type === 'FAQPage') {
            $node['mainEntity'] = array_map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
            ], $content->faqPairs());
        }
        if ($content->type === 'glossary' && $content->glossaryTerms() !== []) {
            $node['@type'] = 'DefinedTermSet';
            $node['hasDefinedTerm'] = array_map(fn (array $term) => [
                '@type' => 'DefinedTerm',
                'name' => $term['term'],
                'description' => $term['definition'],
            ], $content->glossaryTerms());
        }

        return $node;
    }

    private function organization(SiteSetting $site, string $url): array
    {
        $node = [
            '@type' => 'Organization',
            'name' => $site->organization_name ?: $site->site_name,
            'url' => $url,
            'description' => $site->organization_description ?: $site->default_description,
        ];
        $logo = $site->assetAbsolute($site->organization_logo) ?: $site->assetAbsolute($site->logo) ?: $site->faviconUrl();
        if ($logo) {
            $node['logo'] = $logo;
        }
        if ($site->contact_email) {
            $node['email'] = $site->contact_email;
        }
        $same = array_column($site->socialLinks(), 'url');
        if ($same !== []) {
            $node['sameAs'] = $same;
        }

        return $node;
    }

    private function breadcrumbs(SiteSetting $site, array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn (array $crumb, int $index) => array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb[0],
                'item' => $crumb[1] ?: null,
            ]))->all(),
        ];
    }

    private function customGraph(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? [$decoded] : [];
    }

    private function meta(
        SiteSetting $site,
        string $title,
        string $description,
        string $canonical,
        string $robots,
        ?string $image,
        array $graph,
        array $breadcrumbs,
        ?string $ogTitle = null,
        ?string $ogDescription = null,
        ?string $twitterTitle = null,
        ?string $twitterDescription = null,
        ?string $twitterImage = null,
        ?string $prev = null,
        ?string $next = null,
    ): SeoMeta {
        $payload = ['@context' => 'https://schema.org', '@graph' => array_values($graph)];

        return new SeoMeta(
            $title,
            $description,
            $canonical,
            $robots,
            $ogTitle ?: ($site->og_title ?: $title),
            $ogDescription ?: ($site->og_description ?: $description),
            $image,
            $twitterTitle ?: ($site->twitter_title ?: $title),
            $twitterDescription ?: ($site->twitter_description ?: $description),
            $twitterImage ?: ($site->assetAbsolute($site->twitter_image) ?: $image),
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            $breadcrumbs,
            $prev,
            $next,
        );
    }
}
