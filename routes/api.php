<?php

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\SafetyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/token', [ApiController::class, 'token'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [ApiController::class, 'logout']);
        Route::get('/profile', [ApiController::class, 'profile']);
        Route::post('/matchmaking/find', [MatchController::class, 'find'])->middleware('throttle:matchmaking');
        Route::get('/matches/poll', [MatchController::class, 'poll'])->middleware('throttle:signal');
        Route::post('/matches/signal', [MatchController::class, 'signal'])->middleware('throttle:signal');
        Route::post('/matches/next', [MatchController::class, 'next'])->middleware('throttle:next');
        Route::post('/matches/end', [MatchController::class, 'end']);
        Route::post('/reports', [SafetyController::class, 'report'])->middleware('throttle:reports');
        Route::post('/blocks', [SafetyController::class, 'block'])->middleware('throttle:blocks');
        Route::get('/blocks', [ApiController::class, 'blocks']);
        Route::delete('/blocks/{block}', [ApiController::class, 'unblock']);
    });
});
