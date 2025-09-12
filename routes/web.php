<?php

use App\Http\Controllers\StripeEventController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/events', StripeEventController::class)
    ->withoutMiddleware(VerifyCsrfToken::class);
