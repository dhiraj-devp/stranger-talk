<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'socials' => 'array',
            'robots_disallow_all' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'site_name' => 'Koko Meet',
            'tagline' => 'Random 1-to-1 video chat',
            'default_title' => 'Random Video Chat India – Meet New People | Koko Meet',
            'default_description' => 'Meet new people in India through random one-to-one video chat on Koko Meet. Start a live conversation, talk face-to-face and move on whenever you want.',
            'keywords' => 'random video chat India, random video call India, video chat with strangers India',
            'organization_name' => 'Koko Meet',
            'organization_description' => 'Koko Meet is a browser-based random one-to-one video chat platform for adults in India.',
            'theme_color' => '#7c3aed',
            'language' => 'en',
            'locale' => 'en',
            'timezone' => 'UTC',
            'primary_color' => '#7c3aed',
            'secondary_color' => '#a78bfa',
            'footer_text' => 'One-to-one random video conversations.',
            'copyright' => 'Koko Meet',
            'header_cta_label' => 'Start Video Chat',
            'header_cta_url' => '/login',
            'robots_disallow_all' => false,
        ];
    }

    public static function current(): self
    {
        try {
            return Cache::remember('site.settings', 300, function () {
                return static::query()->first() ?? static::query()->create(static::defaults());
            });
        } catch (\Throwable) {
            return new static(static::defaults());
        }
    }

    public static function forgetCache(): void
    {
        Cache::forget('site.settings');
    }

    public function absolute(string $path = '/'): string
    {
        $base = rtrim((string) ($this->canonical_base ?: config('app.url')), '/');
        if ($path === '' || $path === '/') {
            return $base.'/';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $base.'/'.ltrim($path, '/');
    }

    public function assetAbsolute(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $url = Storage::disk('public')->url($path);

        return str_starts_with($url, 'http') ? $url : $this->absolute($url);
    }

    public function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    public function headerLogoUrl(): ?string
    {
        return $this->publicUrl($this->header_logo ?: $this->logo ?: $this->logo_dark);
    }

    public function footerLogoUrl(): ?string
    {
        return $this->publicUrl($this->footer_logo ?: $this->logo ?: $this->logo_dark);
    }

    public function faviconUrl(): string
    {
        return $this->publicUrl($this->favicon) ?: asset('favicon.svg');
    }

    public function socialLinks(): array
    {
        return collect($this->socials ?? [])
            ->filter(fn ($row) => is_array($row) && isset($row['url'], $row['label']) && str_starts_with((string) $row['url'], 'https://'))
            ->map(fn ($row) => ['label' => (string) $row['label'], 'url' => (string) $row['url']])
            ->values()
            ->all();
    }
}
