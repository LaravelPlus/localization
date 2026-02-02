<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use LaravelPlus\Localization\Cache\TranslationCache;
use LaravelPlus\Localization\Contracts\TranslationServiceInterface;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Models\Translation;
use LaravelPlus\Localization\Repositories\LanguageRepository;
use LaravelPlus\Localization\Repositories\TranslationRepository;

final class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        private readonly TranslationRepository $repository,
        private readonly LanguageRepository $languageRepository,
        private readonly TranslationCache $cache,
    ) {}

    /**
     * @return LengthAwarePaginator<Translation>
     */
    public function paginate(?int $languageId = null, ?string $group = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($languageId, $group, $search, $perPage);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getForLocale(string $locale): array
    {
        return $this->cache->allForLocale($locale);
    }

    /**
     * @return Collection<int, string>
     */
    public function getGroupsForLocale(string $locale): Collection
    {
        $language = $this->languageRepository->findByCode($locale);

        if (! $language) {
            return new Collection;
        }

        return $this->repository->getDistinctGroups($language->id);
    }

    /**
     * @return Collection<int, string>
     */
    public function getAllGroups(): Collection
    {
        return $this->repository->getDistinctGroups();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Translation
    {
        $translation = $this->repository->create($data);

        $language = $this->languageRepository->findOrFail((int) $data['language_id']);
        $this->cache->flush($language->code);

        return $translation;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Translation $translation, array $data): Translation
    {
        $translation = $this->repository->update($translation, $data);

        $language = $this->languageRepository->findOrFail($translation->language_id);
        $this->cache->flush($language->code);

        return $translation;
    }

    public function delete(Translation $translation): void
    {
        $language = $this->languageRepository->findOrFail($translation->language_id);

        $this->repository->delete($translation);
        $this->cache->flush($language->code);
    }

    public function findOrFail(int $id): Translation
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, string>  $translations
     */
    public function importFromArray(string $locale, string $group, array $translations, bool $overwrite = false): int
    {
        $language = $this->languageRepository->findByCode($locale);

        if (! $language) {
            return 0;
        }

        $count = $this->repository->upsertBulk($language->id, $group, $translations, $overwrite);

        $this->cache->flush($locale);

        return $count;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function exportToArray(?string $locale = null, ?string $group = null): array
    {
        if ($locale !== null) {
            $translations = $this->getForLocale($locale);

            if ($group !== null) {
                return [$group => $translations[$group] ?? []];
            }

            return $translations;
        }

        $result = [];
        $languages = $this->languageRepository->getActive();

        foreach ($languages as $language) {
            $langTranslations = $this->getForLocale($language->code);

            if ($group !== null) {
                $result[$language->code] = [$group => $langTranslations[$group] ?? []];
            } else {
                $result[$language->code] = $langTranslations;
            }
        }

        return $result;
    }
}
