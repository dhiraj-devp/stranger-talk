<?php

use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'landing'])->name('landing');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/guidelines', [PageController::class, 'guidelines'])->name('guidelines');
Route::get('/health', HealthController::class)->name('health');
Route::get('/sitemap.xml', function () {
    $urls = [route('landing'), route('privacy'), route('terms'), route('guidelines')];
    $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $url) {
        $body .= '<url><loc>'.e($url).'</loc></url>';
    }
    $body .= '</urlset>';

    return response($body, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google')->middleware('throttle:google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback')->middleware('throttle:google');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout.all');
    Route::get('/banned', [PageController::class, 'banned'])->name('banned');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile/setup', [ProfileController::class, 'setup'])->name('profile.setup');
    Route::post('/profile/setup', [ProfileController::class, 'storeSetup'])->name('profile.setup.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
});

Route::middleware(['auth', 'active', 'profile'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/blocks', [SafetyController::class, 'index'])->name('blocks');
    Route::delete('/blocks/{block}', [SafetyController::class, 'unblock'])->name('blocks.destroy');
    Route::get('/video', [VideoController::class, 'show'])->name('video');
    Route::get('/webrtc.js', function () {
        return response()->file(resource_path('js/webrtc.js'), [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    })->name('webrtc.script');

    Route::post('/match/find', [MatchController::class, 'find'])->middleware('throttle:matchmaking')->name('match.find');
    Route::get('/match/poll', [MatchController::class, 'poll'])->middleware('throttle:signal')->name('match.poll');
    Route::post('/match/signal', [MatchController::class, 'signal'])->middleware('throttle:signal')->name('match.signal');
    Route::post('/match/next', [MatchController::class, 'next'])->middleware('throttle:next')->name('match.next');
    Route::post('/match/end', [MatchController::class, 'end'])->name('match.end');
    Route::post('/match/leave', [MatchController::class, 'leave'])->name('match.leave');
    Route::post('/match/report', [SafetyController::class, 'report'])->middleware('throttle:reports')->name('match.report');
    Route::post('/match/block', [SafetyController::class, 'block'])->middleware('throttle:blocks')->name('match.block');
});

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [PanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/live', [PanelController::class, 'live'])->name('live');
    Route::get('/users', [PanelController::class, 'users'])->name('users');
    Route::get('/users/{user}', [PanelController::class, 'showUser'])->name('users.show');
    Route::post('/users/{user}/ban', [PanelController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/unban', [PanelController::class, 'unban'])->name('users.unban');
    Route::delete('/users/{user}', [PanelController::class, 'destroy'])->name('users.destroy');
    Route::get('/bans', [PanelController::class, 'bans'])->name('bans');
    Route::get('/matches', [PanelController::class, 'matches'])->name('matches');
    Route::get('/audit', [PanelController::class, 'audit'])->name('audit');
    Route::get('/reports', [PanelController::class, 'reports'])->name('reports');
    Route::post('/reports/{report}', [PanelController::class, 'updateReport'])->name('reports.update');
    Route::get('/health', [PanelController::class, 'health'])->name('health');
});
