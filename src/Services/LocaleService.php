<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Services;

use LaravelPlus\Localization\Cache\TranslationCache;
use LaravelPlus\Localization\Repositories\LanguageRepository;

final class LocaleService
{
    public function __construct(
        private readonly LanguageRepository $languageRepository,
        private readonly TranslationCache $cache,
    ) {}

    /**
     * Get translations for the current locale (for Inertia sharing).
     *
     * @return array<string, mixed>
     */
    public function getTranslationsForCurrentLocale(): array
    {
        $locale = app()->getLocale();

        return $this->cache->allForLocale($locale);
    }

    /**
     * Get active languages as an array suitable for Inertia sharing.
     *
     * @return array<int, array{code: string, name: string, native_name: string, direction: string, is_default: bool}>
     */
    public function getActiveLanguages(): array
    {
        return $this->languageRepository->getActive()
            ->map(fn ($language): array => [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'direction' => $language->direction,
                'is_default' => $language->is_default,
            ])
            ->all();
    }
}
