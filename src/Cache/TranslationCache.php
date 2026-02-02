<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Cache;

use Illuminate\Support\Facades\Cache;
use LaravelPlus\Localization\Contracts\TranslationLoaderInterface;

final class TranslationCache implements TranslationLoaderInterface
{
    public function __construct(private readonly TranslationLoaderInterface $loader) {}

    /**
     * @return array<string, string>
     */
    public function load(string $locale, string $group, ?string $namespace = null): array
    {
        if (! config('localization.cache.enabled', true)) {
            return $this->loader->load($locale, $group, $namespace);
        }

        $key = $this->cacheKey("load.{$locale}.{$group}.{$namespace}");
        $ttl = (int) config('localization.cache.ttl', 3600);

        return $this->store()->remember($key, $ttl, fn (): array => $this->loader->load($locale, $group, $namespace));
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function allForLocale(string $locale): array
    {
        if (! config('localization.cache.enabled', true)) {
            return $this->loader->allForLocale($locale);
        }

        $key = $this->cacheKey("all.{$locale}");
        $ttl = (int) config('localization.cache.ttl', 3600);

        return $this->store()->remember($key, $ttl, fn (): array => $this->loader->allForLocale($locale));
    }

    /**
     * Flush cache for a specific locale or all locales.
     */
    public function flush(?string $locale = null): void
    {
        $store = $this->store();

        if ($locale !== null) {
            $store->forget($this->cacheKey("load.{$locale}.*"));
            $store->forget($this->cacheKey("all.{$locale}"));

            return;
        }

        // Flush all by forgetting the tagged keys - since we can't enumerate keys
        // on all drivers, we use a version key approach
        $store->forget($this->cacheKey('version'));
    }

    private function cacheKey(string $suffix): string
    {
        $prefix = config('localization.cache.prefix', 'localization');

        return "{$prefix}.{$suffix}";
    }

    private function store(): \Illuminate\Contracts\Cache\Repository
    {
        $storeName = config('localization.cache.store');

        return $storeName ? Cache::store($storeName) : Cache::store();
    }
}
