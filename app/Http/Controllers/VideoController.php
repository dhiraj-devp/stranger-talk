<?php

namespace App\Http\Controllers;

use App\Models\MatchPreference;
use App\Support\Countries;
use App\Support\IceServers;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function show(): View
    {
        $reverb = null;
        if (config('broadcasting.default') === 'reverb' && config('broadcasting.connections.reverb.key')) {
            $reverb = [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => (int) config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme'),
            ];
        }

        $preference = MatchPreference::query()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['gender_preference' => MatchPreference::ANYONE, 'country_preference' => null]
        );

        return view('video', [
            'iceServers' => IceServers::list(),
            'reverb' => $reverb,
            'preference' => $preference,
            'countries' => Countries::all(),
        ]);
    }
}
