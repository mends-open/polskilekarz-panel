<?php

use App\Http\Controllers\ChatwootEventController;
use App\Http\Controllers\StripeEventController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/events', StripeEventController::class)
    ->withoutMiddleware('auth');
Route::post('/chatwoot/events', ChatwootEventController::class)
    ->withoutMiddleware('auth');
