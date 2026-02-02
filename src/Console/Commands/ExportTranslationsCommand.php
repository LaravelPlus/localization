<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Console\Commands;

use Illuminate\Console\Command;
use LaravelPlus\Localization\Contracts\TranslationServiceInterface;

final class ExportTranslationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'localization:export
        {path? : Path to export translations to}
        {--locale= : Export only a specific locale}
        {--group= : Export only a specific group}
        {--format=json : Export format (json or php)}';

    /**
     * @var string
     */
    protected $description = 'Export translations from the database to JSON or PHP files';

    public function handle(TranslationServiceInterface $service): int
    {
        $path = $this->argument('path') ?? lang_path();
        $locale = $this->option('locale');
        $group = $this->option('group');
        $format = $this->option('format') ?? 'json';

        $translations = $service->exportToArray($locale, $group);

        if ($translations === []) {
            $this->info('No translations to export.');

            return self::SUCCESS;
        }

        $totalExported = 0;

        if ($locale !== null) {
            // Single locale export
            $totalExported += $this->exportLocale($path, $locale, $translations, $format);
        } else {
            // Multi-locale export
            foreach ($translations as $localeCode => $groups) {
                $totalExported += $this->exportLocale($path, $localeCode, $groups, $format);
            }
        }

        $this->info("Total exported: {$totalExported} groups.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array<string, string>>  $groups
     */
    private function exportLocale(string $path, string $locale, array $groups, string $format): int
    {
        $count = 0;

        foreach ($groups as $group => $translations) {
            if ($translations === []) {
                continue;
            }

            if ($group === '*') {
                // JSON file for the locale
                $filePath = "{$path}/{$locale}.json";
                $dir = dirname($filePath);

                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->info("Exported JSON for [{$locale}] to {$filePath}");
            } elseif ($format === 'php') {
                $filePath = "{$path}/{$locale}/{$group}.php";
                $dir = dirname($filePath);

                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $nested = $this->unflatten($translations);
                $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($nested, true) . ";\n";
                file_put_contents($filePath, $content);
                $this->info("Exported PHP for [{$locale}/{$group}] to {$filePath}");
            } else {
                $filePath = "{$path}/{$locale}/{$group}.json";
                $dir = dirname($filePath);

                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->info("Exported JSON for [{$locale}/{$group}] to {$filePath}");
            }

            $count++;
        }

        return $count;
    }

    /**
     * Unflatten dot-notation keys into nested arrays.
     *
     * @param  array<string, string>  $array
     * @return array<string, mixed>
     */
    private function unflatten(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $keys = explode('.', $key);
            $current = &$result;

            foreach ($keys as $i => $segment) {
                if ($i === count($keys) - 1) {
                    $current[$segment] = $value;
                } else {
                    if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                        $current[$segment] = [];
                    }
                    $current = &$current[$segment];
                }
            }
        }

        return $result;
    }
}
