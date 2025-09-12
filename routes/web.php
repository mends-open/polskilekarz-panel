<?php

use App\Http\Controllers\StripeEventController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/events', StripeEventController::class);
