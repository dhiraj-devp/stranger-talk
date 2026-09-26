<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsRecorder;
use App\Services\GoogleAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret') || ! config('services.google.redirect')) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in is not configured yet.',
            ]);
        }

        return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])->redirect();
    }

    public function callback(GoogleAccountService $accounts): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
            $user = $accounts->authenticate($google);
        } catch (RuntimeException $e) {
            return redirect()->route('login')->withErrors(['google' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::warning('Google sign-in failed.');

            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in was cancelled or could not be completed.',
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();
        $user->forceFill(['status' => 'active', 'last_seen_at' => now()])->save();
        app(AnalyticsRecorder::class)->track($user, 'login');

        return redirect()->route($user->profileComplete() ? 'home' : 'profile.setup');
    }
}
