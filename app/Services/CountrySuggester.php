<?php

namespace App\Services;

use App\Support\Countries;
use Illuminate\Http\Request;

class CountrySuggester
{
    public function suggest(Request $request): ?string
    {
        $header = strtoupper(trim((string) $request->headers->get('CF-IPCountry', '')));
        if ($header === '' || $header === 'XX' || $header === 'T1') {
            return null;
        }

        return Countries::valid($header) ? $header : null;
    }
}
