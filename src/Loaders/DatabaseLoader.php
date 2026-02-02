<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Loaders;

use LaravelPlus\Localization\Contracts\TranslationLoaderInterface;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Models\Translation;

final class DatabaseLoader implements TranslationLoaderInterface
{
    /**
     * @return array<string, string>
     */
    public function load(string $locale, string $group, ?string $namespace = null): array
    {
        $language = Language::query()
            ->where('code', $locale)
            ->where('is_active', true)
            ->first();

        if (! $language) {
            return [];
        }

        return Translation::query()
            ->where('language_id', $language->id)
            ->where('group', $group)
            ->pluck('value', 'key')
            ->all();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function allForLocale(string $locale): array
    {
        $language = Language::query()
            ->where('code', $locale)
            ->where('is_active', true)
            ->first();

        if (! $language) {
            return [];
        }

        $translations = Translation::query()
            ->where('language_id', $language->id)
            ->get(['group', 'key', 'value']);

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->group][$translation->key] = $translation->value;
        }

        return $result;
    }
}
