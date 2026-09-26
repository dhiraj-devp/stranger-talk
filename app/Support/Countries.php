<?php

namespace App\Support;

class Countries
{
    public static function all(): array
    {
        return config('countries', []);
    }

    public static function valid(?string $code): bool
    {
        return is_string($code) && isset(self::all()[strtoupper($code)]);
    }

    public static function name(?string $code): string
    {
        $code = strtoupper((string) $code);

        return self::all()[$code] ?? $code;
    }

    public static function flag(?string $code): string
    {
        $code = strtoupper((string) $code);
        if (! self::valid($code)) {
            return '';
        }

        $flag = '';
        foreach (str_split($code) as $char) {
            $flag .= mb_chr(127397 + ord($char), 'UTF-8');
        }

        return $flag;
    }

    public static function label(?string $code): string
    {
        if (! self::valid($code)) {
            return 'Select country';
        }

        return trim(self::flag($code).' '.self::name($code));
    }
}
