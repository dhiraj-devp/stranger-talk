<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Content extends Model
{
    public const TYPES = ['page', 'post', 'guide', 'faq', 'glossary'];

    public const RESERVED = [
        'login', 'home', 'video', 'admin', 'privacy', 'terms', 'guidelines', 'health', 'auth', 'api',
        'profile', 'match', 'blocks', 'banned', 'blog', 'guides', 'up', 'storage', 'css', 'js',
    ];

    protected $table = 'contents';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'featured' => 'boolean',
            'related_ids' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'content_tag');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', 'published')->where(function (Builder $query) {
            $query->whereNull('published_at')->orWhere('published_at', '<=', now());
        });
    }

    public function publicPath(): string
    {
        return match ($this->type) {
            'post' => '/blog/'.$this->slug,
            'guide' => '/guides/'.$this->slug,
            default => '/'.$this->slug,
        };
    }

    public function blocks(): array
    {
        $blocks = [];
        foreach (preg_split("/\n{2,}/", trim((string) $this->body)) ?: [] as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            if (str_starts_with($chunk, '## ')) {
                $blocks[] = ['type' => 'h2', 'text' => trim(substr($chunk, 3))];
            } else {
                $blocks[] = ['type' => 'p', 'text' => $chunk];
            }
        }

        return $blocks;
    }

    public function faqPairs(): array
    {
        $pairs = [];
        foreach (preg_split("/\n{2,}/", trim((string) $this->body)) ?: [] as $chunk) {
            if (preg_match('/^Q:\s*(.+)\R+A:\s*(.+)$/s', trim($chunk), $matches)) {
                $pairs[] = ['q' => trim($matches[1]), 'a' => trim($matches[2])];
            }
        }

        return $pairs;
    }

    public function glossaryTerms(): array
    {
        $terms = [];
        foreach (preg_split("/\R/", trim((string) $this->body)) ?: [] as $line) {
            if (! str_contains($line, ' | ')) {
                continue;
            }
            [$term, $definition] = array_map('trim', explode(' | ', $line, 2));
            if ($term !== '' && $definition !== '') {
                $terms[] = ['term' => $term, 'definition' => $definition];
            }
        }

        return $terms;
    }

    public function relatedItems()
    {
        $ids = array_values(array_filter(array_map('intval', $this->related_ids ?? [])));
        $picked = $ids === [] ? collect() : static::query()->public()->whereIn('id', $ids)->limit(3)->get();
        $more = $this->category_id
            ? static::query()->public()->where('category_id', $this->category_id)->where('id', '!=', $this->id)->limit(3)->get()
            : collect();

        return $picked->concat($more)->unique('id')->take(5)->values();
    }
}
