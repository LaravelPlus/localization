<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Services;

use LaravelPlus\GlobalSettings\Models\Setting;
use RuntimeException;

final class LocalizationSettingsService
{
    /**
     * Config keys managed by this service and their types.
     *
     * @var array<string, string>
     */
    private const CONFIG_KEYS = [
        'static.driver' => 'string',
        'content.enabled' => 'boolean',
        'content.strategy' => 'string',
        'detection.strategy' => 'string',
        'detection.chain' => 'array',
        'detection.user_column' => 'string',
        'cache.enabled' => 'boolean',
        'cache.ttl' => 'integer',
    ];

    /**
     * Get all localization settings with GlobalSettings values taking priority over config defaults.
     *
     * Returns a nested array structure matching the config hierarchy.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $flat = [];

        foreach (self::CONFIG_KEYS as $key => $type) {
            $flat[$key] = $this->getValue($key, $type);
        }

        return $this->unflatten($flat);
    }

    /**
     * Update localization settings via GlobalSettings.
     *
     * Accepts nested array input (as submitted by forms) and flattens it for storage.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        if (!class_exists(Setting::class)) {
            throw new RuntimeException('GlobalSettings package is not installed. Settings cannot be updated.');
        }

        $flat = $this->flatten($data);

        foreach ($flat as $key => $value) {
            if (!array_key_exists($key, self::CONFIG_KEYS)) {
                continue;
            }

            $storedValue = match (self::CONFIG_KEYS[$key]) {
                'boolean' => $value ? '1' : '0',
                'array' => is_array($value) ? json_encode($value) : (string) $value,
                'integer' => (string) (int) $value,
                default => (string) $value,
            };

            Setting::set("localization.{$key}", $storedValue);
        }
    }

    /**
     * Get a single setting value with type coercion.
     */
    private function getValue(string $key, string $type): mixed
    {
        $settingValue = null;

        if (class_exists(Setting::class)) {
            $settingValue = Setting::get("localization.{$key}");
        }

        if ($settingValue !== null) {
            return $this->castValue($settingValue, $type);
        }

        return config("localization.{$key}");
    }

    /**
     * Cast a stored string value to the appropriate PHP type.
     */
    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => in_array($value, ['1', 'true', true, 1], true),
            'integer' => (int) $value,
            'array' => is_array($value) ? $value : (is_string($value) ? (json_decode($value, true) ?? []) : []),
            default => $value,
        };
    }

    /**
     * Convert a flat dot-notation array to a nested array.
     *
     * @param  array<string, mixed>  $flat
     * @return array<string, mixed>
     */
    private function unflatten(array $flat): array
    {
        $nested = [];

        foreach ($flat as $key => $value) {
            data_set($nested, $key, $value);
        }

        return $nested;
    }

    /**
     * Flatten a nested array to dot-notation keys.
     *
     * @param  array<string, mixed>  $data
     * @param  string  $prefix
     * @return array<string, mixed>
     */
    private function flatten(array $data, string $prefix = ''): array
    {
        $flat = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($value) && !array_is_list($value)) {
                $flat = array_merge($flat, $this->flatten($value, $fullKey));
            } else {
                $flat[$fullKey] = $value;
            }
        }

        return $flat;
    }
}
