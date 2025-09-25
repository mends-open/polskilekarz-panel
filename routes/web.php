<?php

use App\Http\Controllers\StoreChatwootDashboardContextController;
use App\Http\Controllers\StripeEventController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/events', StripeEventController::class)
    ->withoutMiddleware('auth');

Route::post('chatwoot/context', StoreChatwootDashboardContextController::class)
    ->name('chatwoot.context.store')
    ->withoutMiddleware('auth');
