<?php

namespace App\Services;

use App\Models\Content;

class SeoAuditor
{
    public function forContent(Content $content): array
    {
        $issues = [];
        $title = $content->seo_title ?: $content->title;
        $description = $content->seo_description ?: $content->excerpt;
        $length = mb_strlen($title);
        if ($length < 30 || $length > 65) {
            $issues[] = $this->issue('warning', 'Title is '.$length.' characters. A clear title is often around 50–60.');
        }
        if (! $description) {
            $issues[] = $this->issue('warning', 'Add a meta description or an excerpt.');
        } else {
            $d = mb_strlen($description);
            if ($d < 70 || $d > 170) {
                $issues[] = $this->issue('info', 'Description is '.$d.' characters. 140–160 is a useful range, not a rule.');
            }
        }
        if ($content->featured_image && ! $content->image_alt) {
            $issues[] = $this->issue('warning', 'The featured image has no alt text.');
        } elseif (! $content->featured_image && in_array($content->type, ['post', 'guide'], true)) {
            $issues[] = $this->issue('info', 'This entry has no featured image.');
        }
        if ($content->status === 'published' && ! $content->robots_index) {
            $issues[] = $this->issue('warning', 'This published page is noindex, so it is excluded from the sitemap.');
        }
        if ($content->type === 'faq' && $content->faqPairs() === []) {
            $issues[] = $this->issue('warning', 'No FAQ schema was added. Use Q: and A: paragraphs if this page is a real FAQ.');
        }
        if ($content->json_ld) {
            $issues[] = $this->issue('info', 'Custom JSON-LD replaces the generated schema and must describe the visible page.');
        }
        if (trim((string) $content->body) === '') {
            $issues[] = $this->issue('error', 'The page has no body content.');
        }

        return $issues;
    }

    private function issue(string $level, string $message): array
    {
        return ['level' => $level, 'message' => $message];
    }
}
