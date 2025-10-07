<?php

use App\Http\Controllers\ChatwootEventController;
use App\Http\Controllers\StripeEventController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::post('/events/stripe', StripeEventController::class)
    ->withoutMiddleware('auth');
Route::post('/events/chatwoot', ChatwootEventController::class)
    ->withoutMiddleware('auth');

Route::get('/translations/{locale?}', TranslationController::class)
    ->where('locale', '[a-zA-Z_\-]+')
    ->withoutMiddleware('auth');
