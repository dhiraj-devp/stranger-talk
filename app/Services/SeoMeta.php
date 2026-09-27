<?php

namespace App\Services;

class SeoMeta
{
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $robots,
        public string $ogTitle,
        public string $ogDescription,
        public ?string $ogImage,
        public string $twitterTitle,
        public string $twitterDescription,
        public ?string $twitterImage,
        public string $jsonLd,
        public array $breadcrumbs = [],
        public ?string $prev = null,
        public ?string $next = null,
    ) {}
}
