<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Static Translation Driver
    |--------------------------------------------------------------------------
    |
    | Determines how static UI translations are loaded.
    | Supported: "file", "database", "hybrid"
    |
    | - file: uses Laravel's default lang/ files
    | - database: loads translations from the database
    | - hybrid: merges file + database (database overrides file)
    |
    */
    'static' => [
        'driver' => env('LOCALIZATION_DRIVER', 'hybrid'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Translation
    |--------------------------------------------------------------------------
    |
    | Enable model content translation via the HasTranslations trait.
    | Strategy: "json_column" stores translations as JSON on the model,
    | "polymorphic_table" uses the content_translations table.
    |
    */
    'content' => [
        'enabled' => true,
        'strategy' => env('LOCALIZATION_CONTENT_STRATEGY', 'polymorphic_table'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Detection Strategy
    |--------------------------------------------------------------------------
    |
    | How the current locale is determined from the request.
    | Supported: "session", "url_prefix", "user_preference", "browser", "chain"
    |
    | The "chain" strategy tries each resolver in order and uses the first match.
    |
    */
    'detection' => [
        'strategy' => env('LOCALIZATION_DETECTION', 'chain'),

        // Resolvers used by the chain strategy (in order of priority)
        'chain' => [
            'session',
            'user_preference',
            'browser',
        ],

        // Column on users table for user_preference resolver
        'user_column' => 'locale',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('LOCALIZATION_CACHE_ENABLED', true),
        'ttl' => env('LOCALIZATION_CACHE_TTL', 3600),
        'prefix' => 'localization',
        'store' => env('LOCALIZATION_CACHE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'enabled' => true,
        'prefix' => 'admin/localizations',
        'middleware' => ['web', 'auth', 'role:super-admin,admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'languages' => 'languages',
        'translations' => 'translations',
        'content_translations' => 'content_translations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Classes
    |--------------------------------------------------------------------------
    |
    | Override these if you want to use custom model classes.
    |
    */
    'models' => [
        'language' => LaravelPlus\Localization\Models\Language::class,
        'translation' => LaravelPlus\Localization\Models\Translation::class,
        'content_translation' => LaravelPlus\Localization\Models\ContentTranslation::class,
    ],

];
