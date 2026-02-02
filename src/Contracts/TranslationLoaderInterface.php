<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Contracts;

interface TranslationLoaderInterface
{
    /**
     * Load translations for a given locale and group.
     *
     * @return array<string, string>
     */
    public function load(string $locale, string $group, ?string $namespace = null): array;

    /**
     * Load all translations for a given locale.
     *
     * @return array<string, array<string, string>>
     */
    public function allForLocale(string $locale): array;
}
