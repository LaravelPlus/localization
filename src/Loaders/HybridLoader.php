<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Loaders;

use LaravelPlus\Localization\Contracts\TranslationLoaderInterface;

final class HybridLoader implements TranslationLoaderInterface
{
    public function __construct(
        private readonly FileLoader $fileLoader,
        private readonly DatabaseLoader $databaseLoader,
    ) {}

    /**
     * @return array<string, string>
     */
    public function load(string $locale, string $group, ?string $namespace = null): array
    {
        $fileTranslations = $this->fileLoader->load($locale, $group, $namespace);
        $dbTranslations = $this->databaseLoader->load($locale, $group, $namespace);

        return array_merge($fileTranslations, $dbTranslations);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function allForLocale(string $locale): array
    {
        $fileTranslations = $this->fileLoader->allForLocale($locale);
        $dbTranslations = $this->databaseLoader->allForLocale($locale);

        $result = $fileTranslations;

        foreach ($dbTranslations as $group => $translations) {
            $result[$group] = array_merge($result[$group] ?? [], $translations);
        }

        return $result;
    }
}
