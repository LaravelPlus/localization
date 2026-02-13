<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Models\Translation;
use LaravelPlus\Localization\Tests\TestCase;
use LaravelPlus\Localization\Tests\User;
use Spatie\Permission\Models\Role;

final class TranslationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $this->admin->assignRole($superAdminRole);

        $this->language = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
        ]);
    }

    public function test_index_displays_translations(): void
    {
        Translation::factory()->forLanguage($this->language)->create(['group' => 'messages', 'key' => 'welcome']);
        Translation::factory()->forLanguage($this->language)->create(['group' => 'messages', 'key' => 'goodbye']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/translations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Localizations/Translations/Index')
            ->has('translations.data', 2)
            ->has('languages')
            ->has('groups')
        );
    }

    public function test_index_filters_by_language(): void
    {
        $german = Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);

        Translation::factory()->forLanguage($this->language)->create(['group' => 'messages', 'key' => 'hello']);
        Translation::factory()->forLanguage($german)->create(['group' => 'messages', 'key' => 'hallo']);

        $response = $this->actingAs($this->admin)
            ->get("/admin/localizations/translations?language_id={$this->language->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('translations.data', 1)
        );
    }

    public function test_index_filters_by_group(): void
    {
        Translation::factory()->forLanguage($this->language)->create(['group' => 'messages', 'key' => 'welcome']);
        Translation::factory()->forLanguage($this->language)->create(['group' => 'validation', 'key' => 'required']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/translations?group=messages');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('translations.data', 1)
        );
    }

    public function test_index_searches_by_key_or_value(): void
    {
        Translation::factory()->forLanguage($this->language)->create(['group' => 'messages', 'key' => 'welcome', 'value' => 'Welcome!']);
        Translation::factory()->forLanguage($this->language)->create(['group' => 'messages', 'key' => 'goodbye', 'value' => 'Goodbye!']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/translations?search=welcome');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('translations.data', 1)
        );
    }

    public function test_create_displays_form(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/localizations/translations/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Localizations/Translations/Create')
            ->has('languages')
        );
    }

    public function test_store_creates_translation(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/translations', [
                'language_id' => $this->language->id,
                'group' => 'messages',
                'key' => 'welcome',
                'value' => 'Welcome to our site!',
            ]);

        $response->assertRedirect('/admin/localizations/translations');

        $this->assertDatabaseHas('translations', [
            'language_id' => $this->language->id,
            'group' => 'messages',
            'key' => 'welcome',
            'value' => 'Welcome to our site!',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/translations', []);

        $response->assertSessionHasErrors(['language_id', 'group', 'key', 'value']);
    }

    public function test_store_validates_duplicate_key(): void
    {
        Translation::factory()->forLanguage($this->language)->create([
            'group' => 'messages',
            'key' => 'welcome',
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/localizations/translations', [
                'language_id' => $this->language->id,
                'group' => 'messages',
                'key' => 'welcome',
                'value' => 'Another welcome',
            ]);

        $response->assertSessionHasErrors(['key']);
    }

    public function test_edit_displays_translation(): void
    {
        $translation = Translation::factory()->forLanguage($this->language)->create([
            'group' => 'messages',
            'key' => 'welcome',
            'value' => 'Welcome!',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/localizations/translations/{$translation->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Localizations/Translations/Edit')
            ->has('translation')
            ->has('languages')
        );
    }

    public function test_update_modifies_translation(): void
    {
        $translation = Translation::factory()->forLanguage($this->language)->create([
            'group' => 'messages',
            'key' => 'welcome',
            'value' => 'Welcome!',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch("/admin/localizations/translations/{$translation->id}", [
                'language_id' => $this->language->id,
                'group' => 'messages',
                'key' => 'welcome',
                'value' => 'Welcome to our updated site!',
            ]);

        $response->assertRedirect('/admin/localizations/translations');

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'value' => 'Welcome to our updated site!',
        ]);
    }

    public function test_destroy_deletes_translation(): void
    {
        $translation = Translation::factory()->forLanguage($this->language)->create([
            'group' => 'messages',
            'key' => 'welcome',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/admin/localizations/translations/{$translation->id}");

        $response->assertRedirect('/admin/localizations/translations');
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_unauthenticated_user_cannot_access_translations(): void
    {
        $response = $this->get('/admin/localizations/translations');

        $response->assertRedirect('/login');
    }
}
