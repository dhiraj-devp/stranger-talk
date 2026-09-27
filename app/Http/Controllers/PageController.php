<?php

namespace App\Http\Controllers;

use App\Services\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private SeoBuilder $seo) {}

    public function landing(): View|RedirectResponse
    {
        if (auth()->check()) {
            $user = auth()->user();

            return redirect()->route($user->profileComplete() ? 'home' : 'profile.setup');
        }

        return view('landing', ['seo' => $this->seo->home()]);
    }

    public function privacy(): View
    {
        return view('legal.privacy', [
            'seo' => $this->seo->page('Privacy', 'How Koko Meet handles account data, live video, reports, and moderation records.', '/privacy'),
        ]);
    }

    public function terms(): View
    {
        return view('legal.terms', [
            'seo' => $this->seo->page('Terms', 'The rules for using Koko Meet, including conduct, account suspension, and call availability.', '/terms'),
        ]);
    }

    public function guidelines(): View
    {
        return view('legal.guidelines', [
            'seo' => $this->seo->page('Community Guidelines', 'What is not allowed on Koko Meet and how to report or block someone during a call.', '/guidelines'),
        ]);
    }

    public function banned(): View
    {
        return view('banned');
    }
}
