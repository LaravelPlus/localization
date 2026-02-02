<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use LaravelPlus\Localization\Models\Translation;

final class TranslationRepository
{
    /**
     * @return LengthAwarePaginator<Translation>
     */
    public function paginate(?int $languageId = null, ?string $group = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Translation::query()->with('language');

        if ($languageId !== null) {
            $query->where('language_id', $languageId);
        }

        if ($group !== null && $group !== '') {
            $query->where('group', $group);
        }

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('group')->orderBy('key')->paginate($perPage);
    }

    /**
     * @return Collection<int, string>
     */
    public function getDistinctGroups(?int $languageId = null): Collection
    {
        $query = Translation::query();

        if ($languageId !== null) {
            $query->where('language_id', $languageId);
        }

        return $query->distinct()->pluck('group');
    }

    public function findOrFail(int $id): Translation
    {
        /** @var Translation */
        return Translation::query()->with('language')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Translation
    {
        /** @var Translation */
        return Translation::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Translation $translation, array $data): Translation
    {
        $translation->update($data);

        return $translation->refresh();
    }

    public function delete(Translation $translation): void
    {
        $translation->delete();
    }

    /**
     * @param  array<string, string>  $translations
     */
    public function upsertBulk(int $languageId, string $group, array $translations, bool $overwrite = false): int
    {
        $count = 0;

        foreach ($translations as $key => $value) {
            $existing = Translation::query()
                ->where('language_id', $languageId)
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            if ($existing && ! $overwrite) {
                continue;
            }

            Translation::query()->updateOrCreate(
                [
                    'language_id' => $languageId,
                    'group' => $group,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                ],
            );

            $count++;
        }

        return $count;
    }
}
