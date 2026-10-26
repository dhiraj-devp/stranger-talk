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
            ['q' => 'What is '.$site.'?', 'a' => $site.' is a random one-to-one video chat. You are paired with one other person for a live conversation.'],
            ['q' => 'How do I start a conversation?', 'a' => 'Sign in with Google, confirm your profile, then start a search.'],
            ['q' => 'How does matching work?', 'a' => config('features.preference_matching')
                ? 'You are paired with one other person who is also looking and who fits the gender and country preferences you both saved.'
                : 'You are paired at random with one other person who is also looking. Gender and country are not used to filter matches.'],
            ['q' => 'Can I skip someone?', 'a' => 'Yes. Next ends the current call and looks for someone else.'],
            ['q' => 'Does '.$site.' record my calls?', 'a' => 'No. Video and audio are not stored by '.$site.'. Media goes directly between browsers when the network allows it. If a relay is configured and a direct connection fails, that relay may carry the call only while it lasts. '.$site.' still does not keep a recording.'],
            ['q' => 'What should I do if someone behaves inappropriately?', 'a' => 'Leave the call, then report or block them. Moderators can review the report and account history. They cannot watch a recording, because none is kept.'],
            ['q' => 'Are gender and country filters available?', 'a' => config('features.preference_matching')
                ? 'Yes. Saved gender and country preferences are used when pairing. Anyone and any country still match freely.'
                : 'You can save those preferences, but they do not change who you are paired with yet.'],
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
