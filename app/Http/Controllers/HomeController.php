<?php

namespace App\Http\Controllers;

use App\Models\MatchPreference;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $preference = MatchPreference::query()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['gender_preference' => MatchPreference::ANYONE, 'country_preference' => null]
        );

        return view('home', ['preference' => $preference]);
    }
}
