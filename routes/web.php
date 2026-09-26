<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/video', [VideoController::class, 'show'])->name('video');
    Route::get('/webrtc.js', function () {
        return response()->file(resource_path('js/webrtc.js'), [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    })->name('webrtc.script');

    Route::post('/match/find', [MatchController::class, 'find'])->name('match.find');
    Route::get('/match/poll', [MatchController::class, 'poll'])->name('match.poll');
    Route::post('/match/signal', [MatchController::class, 'signal'])->name('match.signal');
    Route::post('/match/next', [MatchController::class, 'next'])->name('match.next');
    Route::post('/match/end', [MatchController::class, 'end'])->name('match.end');
    Route::post('/match/leave', [MatchController::class, 'leave'])->name('match.leave');
});
