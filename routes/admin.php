<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelPlus\Localization\Http\Controllers\Admin\LanguageController;
use LaravelPlus\Localization\Http\Controllers\Admin\LocalizationSettingsController;
use LaravelPlus\Localization\Http\Controllers\Admin\TranslationController;

/*
|--------------------------------------------------------------------------
| Localization Admin Routes
|--------------------------------------------------------------------------
|
| These routes provide admin functionality for managing languages
| and translations.
|
*/

Route::prefix(config('localization.admin.prefix', 'admin/localizations'))
    ->middleware(config('localization.admin.middleware', ['web', 'auth', 'role:super-admin,admin']))
    ->name('admin.localizations.')
    ->group(function (): void {
        // Languages
        Route::get('/languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::get('/languages/create', [LanguageController::class, 'create'])->name('languages.create');
        Route::post('/languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::get('/languages/{language}/edit', [LanguageController::class, 'edit'])->name('languages.edit');
        Route::patch('/languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
        Route::delete('/languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');
        Route::post('/languages/{language}/set-default', [LanguageController::class, 'setDefault'])->name('languages.set-default');
        Route::post('/languages/seed-common', [LanguageController::class, 'seedCommon'])->name('languages.seed-common');

        // Translations
        Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
        Route::get('/translations/create', [TranslationController::class, 'create'])->name('translations.create');
        Route::post('/translations', [TranslationController::class, 'store'])->name('translations.store');
        Route::get('/translations/{translation}/edit', [TranslationController::class, 'edit'])->name('translations.edit');
        Route::patch('/translations/{translation}', [TranslationController::class, 'update'])->name('translations.update');
        Route::delete('/translations/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');

        // Settings
        Route::get('/settings', [LocalizationSettingsController::class, 'index'])->name('settings');
        Route::patch('/settings', [LocalizationSettingsController::class, 'update'])->name('settings.update');
    });
