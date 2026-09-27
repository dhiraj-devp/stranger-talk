<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeoRedirect extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public static function map()
    {
        try {
            return Cache::remember('seo.redirects', 300, function () {
                return static::query()->where('enabled', true)->get()->keyBy('from_path');
            });
        } catch (\Throwable) {
            return collect();
        }
    }

    public static function forgetCache(): void
    {
        Cache::forget('seo.redirects');
    }
}
