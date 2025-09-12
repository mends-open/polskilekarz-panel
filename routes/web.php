<?php

use App\Http\Controllers\CloudflareLinkClickController;
use App\Http\Controllers\StripeEventController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/events', StripeEventController::class)
    ->withoutMiddleware('auth');

Route::post('/cloudflare/link-clicks', CloudflareLinkClickController::class)
    ->withoutMiddleware('auth');
