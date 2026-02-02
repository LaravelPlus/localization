<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelPlus\Localization\Models\Language;

final class LanguageRepository
{
    /**
     * @return LengthAwarePaginator<Language>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Language::query()->orderBy('sort_order');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('native_name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, Language>
     */
    public function getActive(): Collection
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getDefault(): ?Language
    {
        return Language::query()
            ->where('is_default', true)
            ->first();
    }

    public function findOrFail(int $id): Language
    {
        /** @var Language */
        return Language::query()->findOrFail($id);
    }

    public function findByCode(string $code): ?Language
    {
        return Language::query()
            ->where('code', $code)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Language
    {
        /** @var Language */
        return Language::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Language $language, array $data): Language
    {
        $language->update($data);

        return $language->refresh();
    }

    public function delete(Language $language): void
    {
        $language->delete();
    }

    public function clearDefault(): void
    {
        Language::query()
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
