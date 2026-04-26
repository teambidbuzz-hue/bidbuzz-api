<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TournamentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (No Auth required)
Route::prefix('public')->group(function () {
    Route::get('tournaments/{tournament}', [TournamentController::class, 'publicShow']);
    Route::post('tournaments/{tournament}/register-player', [PlayerController::class, 'publicRegister']);
    Route::get('tournaments/{tournament}/auction-state', [TournamentController::class, 'publicAuctionState']);
});

Route::prefix('auth')->group(function () {
    // Public routes (with rate limiting)
    Route::post('/send-otp', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:20,1'); // 20 requests per minute

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:30,1'); // 30 verification attempts per minute

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected resource routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tournaments', TournamentController::class);
    Route::apiResource('tournaments.players', PlayerController::class)->except(['show', 'destroy']);
    Route::post('tournaments/{tournament}/players/{player}/sell', [PlayerController::class, 'sell']);
    Route::post('tournaments/{tournament}/players/{player}/revert', [PlayerController::class, 'revert']);
    Route::post('tournaments/{tournament}/players/{player}/reject', [PlayerController::class, 'reject']);
    Route::post('tournaments/{tournament}/players/{player}/mark-unsold', [PlayerController::class, 'markUnsold']);
    Route::post('tournaments/{tournament}/players/{player}/reset-to-pending', [PlayerController::class, 'resetToPending']);
    Route::post('tournaments/{tournament}/auction/pick-random', [PlayerController::class, 'pickRandom']);
    Route::get('tournaments/{tournament}/auction-history', [TournamentController::class, 'auctionHistory']);
    Route::apiResource('tournaments.teams', TeamController::class)->except(['show']);
});
