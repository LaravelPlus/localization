<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Tests;

use Illuminate\Support\Facades\Route;
use Inertia\ServiceProvider as InertiaServiceProvider;
use LaravelPlus\GlobalSettings\GlobalSettingsServiceProvider;
use LaravelPlus\Localization\LocalizationServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            PermissionServiceProvider::class,
            GlobalSettingsServiceProvider::class,
            LocalizationServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('permission.testing', true);

        $app['config']->set('view.paths', [__DIR__ . '/resources/views']);

        $app['config']->set('inertia.testing.ensure_pages_exist', false);

        $app['config']->set('localization.admin.middleware', ['web', 'auth', 'role:super-admin|admin']);

        $app['router']->aliasMiddleware('role', RoleMiddleware::class);
        $app['router']->aliasMiddleware('permission', PermissionMiddleware::class);
        $app['router']->aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);
    }

    protected function defineRoutes($router): void
    {
        Route::get('/login', fn () => 'login')->name('login');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
