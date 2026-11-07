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

    public function page(string $title, string $description, string $path, ?string $documentTitle = null): SeoMeta
    {
        $site = SiteSetting::current();
        $canonical = $site->absolute($path);
        $crumbs = [['Home', $site->absolute('/')], [$title, null]];
        $graph = [
            [
                '@type' => 'WebPage',
                'name' => $documentTitle ?: $title,
                'url' => $canonical,
                'description' => $description,
            ],
            $this->breadcrumbs($site, $crumbs),
        ];
        $fullTitle = $documentTitle ?: $title.' — '.$site->site_name;

        return $this->meta($site, $fullTitle, $description, $canonical, 'index,follow', $site->assetAbsolute($site->og_image), $graph, $crumbs);
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
        $matching = config('features.preference_matching')
            ? 'Matching uses the gender and country preferences both people saved. Anyone and any country still match freely.'
            : 'Matching is random and subject to availability and safety restrictions. Saved gender and country preferences do not currently affect matching.';

        return [
            ['q' => 'What is '.$site.'?', 'a' => $site.' is a random one-to-one video chat platform. Signed-in users can be paired with another available person for a live conversation.'],
            ['q' => 'Is '.$site.' available in India?', 'a' => $site.' is designed and marketed for adults in India and can be accessed through a supported web browser, subject to service availability and applicable requirements.'],
            ['q' => 'How do I start a random video chat?', 'a' => 'Sign in with Google, complete the required profile details, and enter the matching experience. When another person is available, the system can pair you for a conversation.'],
            ['q' => 'Can I choose who I meet?', 'a' => 'No. '.$matching],
            ['q' => 'Can I video chat with girls?', 'a' => config('features.preference_matching')
                ? 'You may meet people of different genders. Saved gender preferences are used when pairing, and a female match is still not guaranteed.'
                : 'You may meet people of different genders, but '.$site.' does not currently guarantee female matches. Gender preferences can be saved but do not currently control matching.'],
            ['q' => 'Can I meet people from my city or state?', 'a' => $site.' does not currently guarantee city- or state-based matching. Matches are random.'],
            ['q' => 'Does '.$site.' support Hindi?', 'a' => 'Participants can use a language they both understand, but '.$site.' does not currently guarantee language-based matching.'],
            ['q' => 'Is '.$site.' free?', 'a' => $site.' does not charge a fee to sign in or start a call, and the application has no paid plan. Your own network and device costs are separate. A conversation still depends on another person being available.'],
            ['q' => 'Do I need to install an app?', 'a' => $site.' is available through a supported web browser. Camera and microphone permissions are requested by the browser when needed.'],
            ['q' => 'Does '.$site.' record calls?', 'a' => $site.' does not store video or audio recordings. Media may pass through a configured relay during a call. That does not mean the platform records the call.'],
            ['q' => 'Can I skip someone?', 'a' => 'Yes. Use Next to leave the current match and search for another available person, subject to the current call flow.'],
            ['q' => 'What should I do if someone behaves inappropriately?', 'a' => 'Leave the conversation and use Block or Report. Review the Community Guidelines and Safety Center. Moderators can review the report and permitted account history. They cannot watch a recording, because none is kept.'],
            ['q' => 'Is '.$site.' anonymous?', 'a' => 'No. Google sign-in and profile setup are required.'],
            ['q' => 'Is '.$site.' only for adults?', 'a' => 'The service is intended for adults aged 18 and above. The application does not currently verify age with a date of birth or an identity document.'],
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
