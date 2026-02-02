<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Loaders;

use Illuminate\Contracts\Translation\Loader;
use LaravelPlus\Localization\Contracts\TranslationLoaderInterface;

final class FileLoader implements TranslationLoaderInterface
{
    public function __construct(private readonly Loader $laravelLoader) {}

    /**
     * @return array<string, string>
     */
    public function load(string $locale, string $group, ?string $namespace = null): array
    {
        if ($group === '*') {
            return $this->loadJson($locale);
        }

        $translations = $this->laravelLoader->load($locale, $group, $namespace ?? '*');

        return $this->flatten($translations);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function allForLocale(string $locale): array
    {
        $result = [];

        $langPath = lang_path($locale);
        if (is_dir($langPath)) {
            foreach (glob($langPath . '/*.php') as $file) {
                $group = basename($file, '.php');
                $result[$group] = $this->load($locale, $group);
            }
        }

        $jsonTranslations = $this->loadJson($locale);
        if ($jsonTranslations !== []) {
            $result['*'] = $jsonTranslations;
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function loadJson(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        /** @var array<string, string> $decoded */
        $decoded = json_decode($content, true);

        return $decoded ?? [];
    }

    /**
     * Flatten nested translation arrays into dot notation.
     *
     * @param  array<string, mixed>  $array
     * @param  string  $prefix
     * @return array<string, string>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $fullKey));
            } else {
                $result[$fullKey] = (string) $value;
            }
        }

        return $result;
    }
}
