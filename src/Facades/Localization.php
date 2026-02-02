<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(?int $languageId = null, ?string $group = null, ?string $search = null, int $perPage = 15)
 * @method static array getForLocale(string $locale)
 * @method static \Illuminate\Database\Eloquent\Collection getGroupsForLocale(string $locale)
 * @method static \Illuminate\Database\Eloquent\Collection getAllGroups()
 * @method static \LaravelPlus\Localization\Models\Translation create(array $data)
 * @method static \LaravelPlus\Localization\Models\Translation update(\LaravelPlus\Localization\Models\Translation $translation, array $data)
 * @method static void delete(\LaravelPlus\Localization\Models\Translation $translation)
 * @method static \LaravelPlus\Localization\Models\Translation findOrFail(int $id)
 * @method static int importFromArray(string $locale, string $group, array $translations, bool $overwrite = false)
 * @method static array exportToArray(?string $locale = null, ?string $group = null)
 *
 * @see \LaravelPlus\Localization\Services\TranslationService
 */
final class Localization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'localization';
    }
}
