<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelPlus\GlobalSettings\Models\Setting;
use LaravelPlus\Localization\Tests\TestCase;
use LaravelPlus\Localization\Tests\User;
use Spatie\Permission\Models\Role;

final class LocalizationSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        // Create users with roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    /**
     * @return array<string, mixed>
     */
    private function validSettingsData(array $overrides = []): array
    {
        return array_replace_recursive([
            'static' => ['driver' => 'hybrid'],
            'content' => ['enabled' => true, 'strategy' => 'json_column'],
            'detection' => [
                'strategy' => 'session',
                'chain' => ['session', 'browser'],
                'user_column' => 'locale',
            ],
            'cache' => ['enabled' => true, 'ttl' => 3600],
        ], $overrides);
    }

    // ========================================
    // AUTHORIZATION TESTS
    // ========================================

    public function test_guests_cannot_access_localization_settings(): void
    {
        $response = $this->get(route('admin.localizations.settings'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_users_cannot_access_localization_settings(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.localizations.settings'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_localization_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.localizations.settings'));

        $response->assertOk();
    }

    public function test_super_admin_can_access_localization_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.localizations.settings'));

        $response->assertOk();
    }

    // ========================================
    // INDEX PAGE TESTS
    // ========================================

    public function test_settings_page_displays_current_config_values(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.localizations.settings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Localizations/Settings')
            ->has('settings')
            ->where('settings.static.driver', config('localization.static.driver'))
            ->where('settings.content.enabled', config('localization.content.enabled'))
            ->where('settings.detection.strategy', config('localization.detection.strategy'))
            ->where('settings.cache.ttl', config('localization.cache.ttl'))
        );
    }

    public function test_settings_page_shows_overridden_values_from_database(): void
    {
        Setting::set('localization.static.driver', 'database');
        Setting::set('localization.cache.ttl', '7200');

        $response = $this->actingAs($this->admin)->get(route('admin.localizations.settings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('settings.static.driver', 'database')
            ->where('settings.cache.ttl', 7200)
        );
    }

    // ========================================
    // UPDATE TESTS
    // ========================================

    public function test_guests_cannot_update_localization_settings(): void
    {
        $response = $this->patch(route('admin.localizations.settings.update'), $this->validSettingsData());

        $response->assertRedirect(route('login'));
    }

    public function test_regular_users_cannot_update_localization_settings(): void
    {
        $response = $this->actingAs($this->user)->patch(route('admin.localizations.settings.update'), $this->validSettingsData());

        $response->assertStatus(403);
    }

    public function test_admin_can_update_localization_settings(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.localizations.settings.update'), $this->validSettingsData([
            'static' => ['driver' => 'database'],
            'content' => ['enabled' => false, 'strategy' => 'json_column'],
            'detection' => [
                'strategy' => 'chain',
                'chain' => ['session', 'user_preference'],
                'user_column' => 'preferred_locale',
            ],
            'cache' => ['enabled' => true, 'ttl' => 7200],
        ]));

        $response->assertRedirect(route('admin.localizations.settings'));
        $response->assertSessionHas('status', 'Localization settings updated successfully.');

        $this->assertDatabaseHas('settings', ['key' => 'localization.static.driver', 'value' => 'database']);
        $this->assertDatabaseHas('settings', ['key' => 'localization.content.enabled', 'value' => '0']);
        $this->assertDatabaseHas('settings', ['key' => 'localization.content.strategy', 'value' => 'json_column']);
        $this->assertDatabaseHas('settings', ['key' => 'localization.detection.strategy', 'value' => 'chain']);
        $this->assertDatabaseHas('settings', ['key' => 'localization.detection.user_column', 'value' => 'preferred_locale']);
        $this->assertDatabaseHas('settings', ['key' => 'localization.cache.enabled', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'localization.cache.ttl', 'value' => '7200']);
    }

    public function test_updated_settings_are_reflected_on_settings_page(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.localizations.settings.update'), $this->validSettingsData([
            'static' => ['driver' => 'file'],
            'content' => ['enabled' => true, 'strategy' => 'polymorphic_table'],
            'detection' => [
                'strategy' => 'url_prefix',
                'chain' => ['session'],
                'user_column' => 'locale',
            ],
            'cache' => ['enabled' => false, 'ttl' => 1800],
        ]));

        $response = $this->actingAs($this->admin)->get(route('admin.localizations.settings'));

        $response->assertInertia(fn ($page) => $page
            ->where('settings.static.driver', 'file')
            ->where('settings.content.enabled', true)
            ->where('settings.content.strategy', 'polymorphic_table')
            ->where('settings.detection.strategy', 'url_prefix')
            ->where('settings.cache.enabled', false)
            ->where('settings.cache.ttl', 1800)
        );
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    public function test_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.localizations.settings.update'), []);

        $response->assertSessionHasErrors([
            'static.driver',
            'content.enabled',
            'content.strategy',
            'detection.strategy',
            'detection.chain',
            'detection.user_column',
            'cache.enabled',
            'cache.ttl',
        ]);
    }

    public function test_validates_driver_enum(): void
    {
        $response = $this->actingAs($this->admin)->patch(
            route('admin.localizations.settings.update'),
            $this->validSettingsData(['static' => ['driver' => 'invalid']]),
        );

        $response->assertSessionHasErrors('static.driver');
    }

    public function test_validates_content_strategy_enum(): void
    {
        $response = $this->actingAs($this->admin)->patch(
            route('admin.localizations.settings.update'),
            $this->validSettingsData(['content' => ['strategy' => 'invalid']]),
        );

        $response->assertSessionHasErrors('content.strategy');
    }

    public function test_validates_detection_strategy_enum(): void
    {
        $response = $this->actingAs($this->admin)->patch(
            route('admin.localizations.settings.update'),
            $this->validSettingsData(['detection' => ['strategy' => 'invalid']]),
        );

        $response->assertSessionHasErrors('detection.strategy');
    }

    public function test_validates_chain_resolvers(): void
    {
        $response = $this->actingAs($this->admin)->patch(
            route('admin.localizations.settings.update'),
            $this->validSettingsData(['detection' => ['chain' => ['invalid_resolver']]]),
        );

        $response->assertSessionHasErrors('detection.chain.0');
    }

    public function test_validates_cache_ttl_not_negative(): void
    {
        $response = $this->actingAs($this->admin)->patch(
            route('admin.localizations.settings.update'),
            $this->validSettingsData(['cache' => ['ttl' => -1]]),
        );

        $response->assertSessionHasErrors('cache.ttl');
    }

    public function test_validates_user_column_max_length(): void
    {
        $response = $this->actingAs($this->admin)->patch(
            route('admin.localizations.settings.update'),
            $this->validSettingsData(['detection' => ['user_column' => str_repeat('a', 256)]]),
        );

        $response->assertSessionHasErrors('detection.user_column');
    }
}
