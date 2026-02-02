<?php

declare(strict_types=1);

namespace LaravelPlus\Localization;

use App\Support\AdminNavigation;
use Illuminate\Support\ServiceProvider;
use LaravelPlus\Localization\Cache\TranslationCache;
use LaravelPlus\Localization\Console\Commands\ExportTranslationsCommand;
use LaravelPlus\Localization\Console\Commands\ImportTranslationsCommand;
use LaravelPlus\Localization\Contracts\LanguageServiceInterface;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;
use LaravelPlus\Localization\Contracts\TranslationLoaderInterface;
use LaravelPlus\Localization\Contracts\TranslationServiceInterface;
use LaravelPlus\Localization\Loaders\DatabaseLoader;
use LaravelPlus\Localization\Loaders\FileLoader;
use LaravelPlus\Localization\Loaders\HybridLoader;
use LaravelPlus\Localization\Repositories\LanguageRepository;
use LaravelPlus\Localization\Repositories\TranslationRepository;
use LaravelPlus\Localization\Resolvers\BrowserResolver;
use LaravelPlus\Localization\Resolvers\ChainResolver;
use LaravelPlus\Localization\Resolvers\SessionResolver;
use LaravelPlus\Localization\Resolvers\UrlPrefixResolver;
use LaravelPlus\Localization\Resolvers\UserPreferenceResolver;
use LaravelPlus\Localization\Services\LanguageService;
use LaravelPlus\Localization\Services\LocaleService;
use LaravelPlus\Localization\Services\LocalizationSettingsService;
use LaravelPlus\Localization\Services\TranslationService;

final class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/localization.php', 'localization');

        $this->registerLoader();
        $this->registerCache();
        $this->registerResolver();
        $this->registerRepositories();
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerResources();
        $this->registerCommands();
        $this->registerAdminNavigation();
    }

    private function registerLoader(): void
    {
        $this->app->singleton(FileLoader::class, fn ($app): FileLoader => new FileLoader(
            $app->make('translation.loader'),
        ));

        $this->app->singleton(DatabaseLoader::class);

        $this->app->singleton(HybridLoader::class, fn ($app): HybridLoader => new HybridLoader(
            $app->make(FileLoader::class),
            $app->make(DatabaseLoader::class),
        ));

        $this->app->singleton(TranslationLoaderInterface::class, function ($app): TranslationLoaderInterface {
            $driver = config('localization.static.driver', 'hybrid');

            return match ($driver) {
                'file' => $app->make(FileLoader::class),
                'database' => $app->make(DatabaseLoader::class),
                default => $app->make(HybridLoader::class),
            };
        });
    }

    private function registerCache(): void
    {
        $this->app->singleton(TranslationCache::class, fn ($app): TranslationCache => new TranslationCache(
            $app->make(TranslationLoaderInterface::class),
        ));
    }

    private function registerResolver(): void
    {
        $this->app->singleton(SessionResolver::class);
        $this->app->singleton(UrlPrefixResolver::class);
        $this->app->singleton(UserPreferenceResolver::class);
        $this->app->singleton(BrowserResolver::class);

        $this->app->singleton(LocaleResolverInterface::class, function ($app): LocaleResolverInterface {
            $strategy = config('localization.detection.strategy', 'chain');

            return match ($strategy) {
                'session' => $app->make(SessionResolver::class),
                'url_prefix' => $app->make(UrlPrefixResolver::class),
                'user_preference' => $app->make(UserPreferenceResolver::class),
                'browser' => $app->make(BrowserResolver::class),
                default => $this->buildChainResolver($app),
            };
        });
    }

    private function buildChainResolver(mixed $app): ChainResolver
    {
        $resolverMap = [
            'session' => SessionResolver::class,
            'url_prefix' => UrlPrefixResolver::class,
            'user_preference' => UserPreferenceResolver::class,
            'browser' => BrowserResolver::class,
        ];

        $chain = config('localization.detection.chain', ['session', 'user_preference', 'browser']);
        $resolvers = [];

        foreach ($chain as $name) {
            if (isset($resolverMap[$name])) {
                $resolvers[] = $app->make($resolverMap[$name]);
            }
        }

        return new ChainResolver($resolvers);
    }

    private function registerRepositories(): void
    {
        $this->app->singleton(LanguageRepository::class);
        $this->app->singleton(TranslationRepository::class);
    }

    private function registerServices(): void
    {
        $this->app->singleton(LanguageServiceInterface::class, fn ($app): LanguageService => new LanguageService(
            $app->make(LanguageRepository::class),
            $app->make(TranslationCache::class),
        ));

        $this->app->singleton(TranslationServiceInterface::class, fn ($app): TranslationService => new TranslationService(
            $app->make(TranslationRepository::class),
            $app->make(LanguageRepository::class),
            $app->make(TranslationCache::class),
        ));

        $this->app->singleton(LocaleService::class, fn ($app): LocaleService => new LocaleService(
            $app->make(LanguageRepository::class),
            $app->make(TranslationCache::class),
        ));

        $this->app->singleton(LocalizationSettingsService::class);

        $this->app->alias(TranslationServiceInterface::class, 'localization');
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/localization.php' => config_path('localization.php'),
            ], 'localization-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'localization-migrations');

            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'localization-seeders');

            $this->publishes([
                __DIR__.'/../skills/localization-development' => base_path('.claude/skills/localization-development'),
            ], 'localization-skills');

            $this->publishes([
                __DIR__.'/../skills/localization-development' => base_path('.github/skills/localization-development'),
            ], 'localization-skills-github');
        }
    }

    private function registerResources(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->isAdminEnabled()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        }
    }

    /**
     * Check if admin routes should be enabled via DB setting or config fallback.
     */
    private function isAdminEnabled(): bool
    {
        if (class_exists(\LaravelPlus\GlobalSettings\Models\Setting::class)) {
            try {
                $dbValue = \LaravelPlus\GlobalSettings\Models\Setting::get('package.localizations.enabled');

                if ($dbValue !== null) {
                    return in_array($dbValue, ['1', 'true', true, 1], true);
                }
            } catch (\Throwable) {
                // Table may not exist yet during migrations
            }
        }

        return (bool) config('localization.admin.enabled', true);
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportTranslationsCommand::class,
                ExportTranslationsCommand::class,
            ]);
        }
    }

    private function registerAdminNavigation(): void
    {
        $this->callAfterResolving(AdminNavigation::class, function (AdminNavigation $nav): void {
            $prefix = config('localization.admin.prefix', 'admin/localizations');

            $nav->register('localizations', 'Localization', 'Languages', [
                ['title' => 'Languages', 'href' => "/{$prefix}/languages", 'icon' => 'Languages'],
                ['title' => 'Translations', 'href' => "/{$prefix}/translations", 'icon' => 'FileText'],
                ['title' => 'Settings', 'href' => "/{$prefix}/settings", 'icon' => 'Settings'],
            ], 30);
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'localization',
            TranslationLoaderInterface::class,
            LocaleResolverInterface::class,
            LanguageServiceInterface::class,
            TranslationServiceInterface::class,
            TranslationCache::class,
            LocaleService::class,
        ];
    }
}
