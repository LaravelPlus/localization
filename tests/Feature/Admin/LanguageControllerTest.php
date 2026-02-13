<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Tests\TestCase;
use LaravelPlus\Localization\Tests\User;
use Spatie\Permission\Models\Role;

final class LanguageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $this->admin->assignRole($superAdminRole);
    }

    public function test_index_displays_languages(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/languages');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Localizations/Languages/Index')
            ->has('languages.data', 2)
        );
    }

    public function test_index_filters_by_search(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/languages?search=German');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('languages.data', 1)
        );
    }

    public function test_create_displays_form(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/languages/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Localizations/Languages/Create')
        );
    }

    public function test_store_creates_language(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/languages', [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'direction' => 'ltr',
                'sort_order' => 3,
            ]);

        $response->assertRedirect('/admin/localizations/languages');

        $this->assertDatabaseHas('languages', [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'direction' => 'ltr',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/languages', []);

        $response->assertSessionHasErrors(['code', 'name', 'native_name', 'direction']);
    }

    public function test_store_validates_unique_code(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);

        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/languages', [
                'code' => 'en',
                'name' => 'English Again',
                'native_name' => 'English Again',
                'direction' => 'ltr',
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_store_validates_direction(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/languages', [
                'code' => 'xx',
                'name' => 'Test',
                'native_name' => 'Test',
                'direction' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['direction']);
    }

    public function test_edit_displays_language(): void
    {
        $language = Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);

        $response = $this->actingAs($this->admin)
            ->get("/admin/localizations/languages/{$language->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Localizations/Languages/Edit')
            ->has('language')
        );
    }

    public function test_update_modifies_language(): void
    {
        $language = Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);

        $response = $this->actingAs($this->admin)
            ->patch("/admin/localizations/languages/{$language->id}", [
                'code' => 'en',
                'name' => 'English Updated',
                'native_name' => 'English',
                'direction' => 'ltr',
            ]);

        $response->assertRedirect('/admin/localizations/languages');

        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'name' => 'English Updated',
        ]);
    }

    public function test_destroy_deletes_language(): void
    {
        $language = Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);

        $response = $this->actingAs($this->admin)
            ->delete("/admin/localizations/languages/{$language->id}");

        $response->assertRedirect('/admin/localizations/languages');
        $this->assertDatabaseMissing('languages', ['id' => $language->id]);
    }

    public function test_destroy_prevents_deleting_default_language(): void
    {
        $language = Language::factory()->default()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);

        $response = $this->actingAs($this->admin)
            ->delete("/admin/localizations/languages/{$language->id}");

        $response->assertRedirect('/admin/localizations/languages');
        $response->assertSessionHasErrors(['language']);
        $this->assertDatabaseHas('languages', ['id' => $language->id]);
    }

    public function test_set_default_changes_default_language(): void
    {
        $english = Language::factory()->default()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English']);
        $german = Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);

        $response = $this->actingAs($this->admin)
            ->post("/admin/localizations/languages/{$german->id}/set-default");

        $response->assertRedirect('/admin/localizations/languages');

        $this->assertDatabaseHas('languages', ['id' => $german->id, 'is_default' => true]);
        $this->assertDatabaseHas('languages', ['id' => $english->id, 'is_default' => false]);
    }

    public function test_seed_common_creates_languages(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/languages/seed-common');

        $response->assertRedirect('/admin/localizations/languages');
        $response->assertSessionHas('status', 'Common languages have been added.');

        $this->assertDatabaseHas('languages', ['code' => 'en', 'is_default' => true]);
        $this->assertDatabaseHas('languages', ['code' => 'de']);
        $this->assertDatabaseHas('languages', ['code' => 'fr']);
        $this->assertDatabaseHas('languages', ['code' => 'ar', 'direction' => 'rtl']);
    }

    public function test_seed_common_is_idempotent(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/localizations/languages/seed-common');

        $countBefore = Language::count();

        $this->actingAs($this->admin)
            ->post('/admin/localizations/languages/seed-common');

        $this->assertEquals($countBefore, Language::count());
    }

    public function test_index_includes_fallback_locale(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/languages');

        $response->assertInertia(fn ($page) => $page
            ->has('fallbackLocale')
        );
    }

    public function test_unauthenticated_user_cannot_access_languages(): void
    {
        $response = $this->get('/admin/localizations/languages');

        $response->assertRedirect('/login');
    }
}
