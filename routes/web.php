<?php

use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'landing'])->name('landing');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/guidelines', [PageController::class, 'guidelines'])->name('guidelines');
Route::get('/health', HealthController::class)->name('health');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/site.webmanifest', ManifestController::class)->name('manifest');
Route::get('/blog', [ContentController::class, 'blog'])->name('blog');
Route::get('/guides', [ContentController::class, 'guides'])->name('guides');
Route::get('/blog/{slug}', [ContentController::class, 'post'])->where('slug', '[a-z0-9\-]+')->name('blog.show');
Route::get('/guides/{slug}', [ContentController::class, 'guide'])->where('slug', '[a-z0-9\-]+')->name('guides.show');

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
    Route::get('/seo', [SeoController::class, 'overview'])->name('seo');
    Route::get('/seo/settings', [SeoController::class, 'settings'])->name('seo.edit');
    Route::post('/seo/settings', [SeoController::class, 'updateSettings'])->name('seo.settings');
    Route::get('/content', [SeoController::class, 'contents'])->name('content');
    Route::get('/content/create', [SeoController::class, 'createContent'])->name('content.create');
    Route::post('/content', [SeoController::class, 'storeContent'])->name('content.store');
    Route::get('/content/{content}/edit', [SeoController::class, 'editContent'])->name('content.edit');
    Route::put('/content/{content}', [SeoController::class, 'updateContent'])->name('content.update');
    Route::delete('/content/{content}', [SeoController::class, 'destroyContent'])->name('content.destroy');
    Route::post('/categories', [SeoController::class, 'storeCategory'])->name('categories.store');
    Route::post('/tags', [SeoController::class, 'storeTag'])->name('tags.store');
    Route::post('/authors', [SeoController::class, 'storeAuthor'])->name('authors.store');
    Route::get('/media', [SeoController::class, 'media'])->name('media');
    Route::post('/media', [SeoController::class, 'storeMedia'])->name('media.store');
    Route::delete('/media/{medium}', [SeoController::class, 'destroyMedia'])->name('media.destroy');
    Route::get('/redirects', [SeoController::class, 'redirects'])->name('redirects');
    Route::post('/redirects', [SeoController::class, 'storeRedirect'])->name('redirects.store');
    Route::delete('/redirects/{redirect}', [SeoController::class, 'destroyRedirect'])->name('redirects.destroy');
    Route::get('/navigation', [SeoController::class, 'navigation'])->name('navigation');
    Route::post('/navigation', [SeoController::class, 'storeNavigation'])->name('navigation.store');
    Route::delete('/navigation/{item}', [SeoController::class, 'destroyNavigation'])->name('navigation.destroy');
});

Route::get('/{slug}', [ContentController::class, 'page'])->where('slug', '[a-z0-9\-]+')->name('content.page');
