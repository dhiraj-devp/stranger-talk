<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VideoMatch;
use App\Services\AnalyticsRecorder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey('login', $request, $credentials['email']);
        $max = (int) config('platform.limits.login', 5);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again later.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['status' => User::ACTIVE, 'last_seen_at' => now()])->save();
        app(AnalyticsRecorder::class)->track($request->user(), 'login');

        return redirect()->route('home');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $key = $this->throttleKey('register', $request);
        $max = (int) config('platform.limits.register', 5);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                'email' => 'Too many registration attempts. Please try again later.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        RateLimiter::hit($key, 60);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => User::ACTIVE,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        try {
            $this->sendVerification($user);
        } catch (\Throwable) {
            \Illuminate\Support\Facades\Log::warning('Verification email could not be sent.');
        }
        app(AnalyticsRecorder::class)->track($user, 'registration');

        return redirect()->route('home');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $key = $this->throttleKey('password', $request, $request->string('email'));
        if (RateLimiter::tooManyAttempts($key, (int) config('platform.limits.password_reset', 5))) {
            return back()->with('status', 'If that email exists, a reset link has been sent.');
        }
        RateLimiter::hit($key, 60);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If that email exists, a reset link has been sent.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset.')
            : back()->withErrors(['email' => 'This reset link is invalid or has expired.']);
    }

    public function sendVerification(User $user): void
    {
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        Mail::raw("Verify your email for Random Video Chat:\n\n{$url}\n\nThis link expires in one hour.", function ($message) use ($user) {
            $message->to($user->email)->subject('Verify your email');
        });
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return back();
        }
        $this->sendVerification($user);

        return back()->with('status', 'Verification link sent.');
    }

    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = $request->user();
        if ($user->id !== $id || ! hash_equals(sha1($user->email), $hash)) {
            abort(403);
        }
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->route('home')->with('status', 'Email verified.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->endMatches($request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function logoutAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->endMatches($user);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function endMatches(?User $user): void
    {
        if (! $user) {
            return;
        }
        VideoMatch::query()->active()->forUser($user->id)->update([
            'status' => VideoMatch::ENDED,
            'end_reason' => 'disconnect',
            'updated_at' => now(),
        ]);
        $user->forceFill(['status' => User::OFFLINE])->save();
    }

    private function throttleKey(string $type, Request $request, ?string $email = null): string
    {
        return $type.'|'.Str::lower((string) $email).'|'.$request->ip();
    }
}
