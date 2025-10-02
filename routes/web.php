<?php

use App\Http\Controllers\ChatwootEventController;
use App\Http\Controllers\StripeEventController;
use Illuminate\Support\Facades\Route;

Route::post('/events/stripe', StripeEventController::class)
    ->withoutMiddleware('auth');
Route::post('/events/chatwoot', ChatwootEventController::class)
    ->withoutMiddleware('auth');
