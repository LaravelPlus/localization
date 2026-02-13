<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Tests\TestCase;
use LaravelPlus\Localization\Tests\User;

final class LocaleSwitchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_switch_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => true]);

        $response = $this->actingAs($this->user)
            ->post('/locale/de');

        $response->assertRedirect();
        $this->assertEquals('de', session('locale'));
    }

    public function test_switching_to_inactive_language_does_not_change_locale(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->post('/locale/de');

        $response->assertRedirect();
        $this->assertNull(session('locale'));
    }

    public function test_switching_to_nonexistent_language_does_not_change_locale(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/locale/xx');

        $response->assertRedirect();
        $this->assertNull(session('locale'));
    }

    public function test_unauthenticated_user_can_switch_locale_via_session(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

        $response = $this->post('/locale/en');

        $response->assertRedirect();
        $this->assertEquals('en', session('locale'));
    }

    public function test_switching_locale_persists_in_session(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_active' => true]);

        $this->actingAs($this->user)
            ->post('/locale/fr');

        $this->assertEquals('fr', session('locale'));

        $this->actingAs($this->user)
            ->post('/locale/en');

        $this->assertEquals('en', session('locale'));
    }
}
