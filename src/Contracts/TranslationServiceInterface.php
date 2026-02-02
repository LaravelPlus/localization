<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use LaravelPlus\Localization\Models\Translation;

interface TranslationServiceInterface
{
    /**
     * @return LengthAwarePaginator<Translation>
     */
    public function paginate(?int $languageId = null, ?string $group = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all translations for a locale, keyed by group.
     *
     * @return array<string, array<string, string>>
     */
    public function getForLocale(string $locale): array;

    /**
     * Get distinct groups for a locale.
     *
     * @return Collection<int, string>
     */
    public function getGroupsForLocale(string $locale): Collection;

    /**
     * Get all distinct groups.
     *
     * @return Collection<int, string>
     */
    public function getAllGroups(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Translation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Translation $translation, array $data): Translation;

    public function delete(Translation $translation): void;

    public function findOrFail(int $id): Translation;

    /**
     * Import translations from an array.
     *
     * @param  array<string, string>  $translations
     */
    public function importFromArray(string $locale, string $group, array $translations, bool $overwrite = false): int;

    /**
     * Export translations to an array.
     *
     * @return array<string, array<string, string>>
     */
    public function exportToArray(?string $locale = null, ?string $group = null): array;
}
