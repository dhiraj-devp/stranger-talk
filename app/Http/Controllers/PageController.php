<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function landing(): View|RedirectResponse
    {
        if (auth()->check()) {
            $user = auth()->user();

            return redirect()->route($user->profileComplete() ? 'home' : 'profile.setup');
        }

        return view('landing');
    }

    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function terms(): View
    {
        return view('legal.terms');
    }

    public function guidelines(): View
    {
        return view('legal.guidelines');
    }

    public function banned(): View
    {
        return view('banned');
    }
}
