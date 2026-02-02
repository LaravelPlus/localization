<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelPlus\Localization\Http\Controllers\LocaleSwitchController;

/*
|--------------------------------------------------------------------------
| Localization Public Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])
    ->post('/locale/{code}', LocaleSwitchController::class)
    ->name('locale.switch');
