<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Console\Commands;

use Illuminate\Console\Command;
use LaravelPlus\Localization\Contracts\TranslationServiceInterface;

final class ImportTranslationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'localization:import
        {path? : Path to the translation files directory}
        {--locale= : Import only for a specific locale}
        {--group= : Import only a specific group}
        {--overwrite : Overwrite existing translations}';

    /**
     * @var string
     */
    protected $description = 'Import translations from JSON/PHP files into the database';

    public function handle(TranslationServiceInterface $service): int
    {
        $path = $this->argument('path') ?? lang_path();
        $locale = $this->option('locale');
        $group = $this->option('group');
        $overwrite = (bool) $this->option('overwrite');

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $totalImported = 0;

        // Import PHP files
        $localeDirs = $locale ? [$locale] : array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($localeDirs as $localeDir) {
            $localePath = "{$path}/{$localeDir}";

            if (! is_dir($localePath)) {
                // Check for JSON files
                if (str_ends_with($localeDir, '.json')) {
                    $jsonLocale = basename($localeDir, '.json');

                    if ($locale !== null && $jsonLocale !== $locale) {
                        continue;
                    }

                    $totalImported += $this->importJsonFile("{$path}/{$localeDir}", $jsonLocale, $service, $overwrite);
                }

                continue;
            }

            $phpFiles = glob("{$localePath}/*.php") ?: [];

            foreach ($phpFiles as $file) {
                $fileGroup = basename($file, '.php');

                if ($group !== null && $fileGroup !== $group) {
                    continue;
                }

                $translations = require $file;

                if (! is_array($translations)) {
                    continue;
                }

                $flattened = $this->flatten($translations);
                $count = $service->importFromArray($localeDir, $fileGroup, $flattened, $overwrite);
                $totalImported += $count;

                $this->info("Imported {$count} translations for [{$localeDir}] group [{$fileGroup}]");
            }
        }

        // Import root-level JSON files
        $jsonFiles = glob("{$path}/*.json") ?: [];

        foreach ($jsonFiles as $jsonFile) {
            $jsonLocale = basename($jsonFile, '.json');

            if ($locale !== null && $jsonLocale !== $locale) {
                continue;
            }

            $totalImported += $this->importJsonFile($jsonFile, $jsonLocale, $service, $overwrite);
        }

        $this->info("Total imported: {$totalImported} translations.");

        return self::SUCCESS;
    }

    private function importJsonFile(string $file, string $locale, TranslationServiceInterface $service, bool $overwrite): int
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return 0;
        }

        /** @var array<string, string>|null $translations */
        $translations = json_decode($content, true);

        if (! is_array($translations)) {
            return 0;
        }

        $count = $service->importFromArray($locale, '*', $translations, $overwrite);
        $this->info("Imported {$count} JSON translations for [{$locale}]");

        return $count;
    }

    /**
     * @param  array<string, mixed>  $array
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
