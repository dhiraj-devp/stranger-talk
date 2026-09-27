<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class NavigationItem extends Model
{
    protected $guarded = [];

    public static function placed(string $location)
    {
        try {
            $all = Cache::remember('seo.nav', 300, function () {
                return static::query()->orderBy('sort_order')->orderBy('id')->get()->groupBy('location');
            });

            return $all->get($location, collect());
        } catch (\Throwable) {
            return collect();
        }
    }

    public static function forgetCache(): void
    {
        Cache::forget('seo.nav');
    }
}
