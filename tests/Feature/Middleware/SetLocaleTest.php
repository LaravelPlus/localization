<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;
use LaravelPlus\Localization\Middleware\SetLocale;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Resolvers\BrowserResolver;
use LaravelPlus\Localization\Resolvers\SessionResolver;
use LaravelPlus\Localization\Resolvers\UserPreferenceResolver;
use LaravelPlus\Localization\Tests\TestCase;
use LaravelPlus\Localization\Tests\User;

final class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_sets_locale_from_resolver(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => true]);

        $resolver = $this->mock(LocaleResolverInterface::class);
        $resolver->shouldReceive('resolve')->once()->andReturn('de');

        $middleware = new SetLocale($resolver);

        $request = Request::create('/');

        $middleware->handle($request, function () {
            $this->assertSame('de', app()->getLocale());

            return response('ok');
        });
    }

    public function test_middleware_does_not_change_locale_when_resolver_returns_null(): void
    {
        $defaultLocale = app()->getLocale();

        $resolver = $this->mock(LocaleResolverInterface::class);
        $resolver->shouldReceive('resolve')->once()->andReturn(null);

        $middleware = new SetLocale($resolver);

        $request = Request::create('/');

        $middleware->handle($request, function () use ($defaultLocale) {
            $this->assertSame($defaultLocale, app()->getLocale());

            return response('ok');
        });
    }

    public function test_session_resolver_reads_from_session(): void
    {
        $resolver = new SessionResolver();

        $request = Request::create('/');
        $request->setLaravelSession(session()->driver());
        session()->put('locale', 'fr');

        $result = $resolver->resolve($request);

        $this->assertSame('fr', $result);
    }

    public function test_session_resolver_returns_null_without_session_locale(): void
    {
        $resolver = new SessionResolver();

        $request = Request::create('/');
        $request->setLaravelSession(session()->driver());

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }

    public function test_browser_resolver_parses_accept_language_header(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => true]);
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

        $resolver = new BrowserResolver();

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7');

        $result = $resolver->resolve($request);

        $this->assertSame('de', $result);
    }

    public function test_browser_resolver_returns_null_for_no_matching_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

        $resolver = new BrowserResolver();

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'zh-CN,zh;q=0.9');

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }

    public function test_user_preference_resolver_reads_from_user(): void
    {
        $user = User::factory()->create();

        // Set locale attribute without persisting (column may not exist)
        $user->setAttribute('locale', 'es');

        $resolver = new UserPreferenceResolver();

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        $result = $resolver->resolve($request);

        $this->assertSame('es', $result);
    }

    public function test_user_preference_resolver_returns_null_for_guest(): void
    {
        $resolver = new UserPreferenceResolver();

        $request = Request::create('/');
        $request->setUserResolver(fn () => null);

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }
}
