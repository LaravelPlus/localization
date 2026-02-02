<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LaravelPlus\Localization\Cache\TranslationCache;
use LaravelPlus\Localization\Contracts\LanguageServiceInterface;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Repositories\LanguageRepository;

final class LanguageService implements LanguageServiceInterface
{
    public function __construct(
        private readonly LanguageRepository $repository,
        private readonly TranslationCache $cache,
    ) {}

    /**
     * @return LengthAwarePaginator<Language>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($search, $perPage);
    }

    /**
     * @return Collection<int, Language>
     */
    public function getActive(): Collection
    {
        return $this->repository->getActive();
    }

    public function getDefault(): ?Language
    {
        return $this->repository->getDefault();
    }

    public function setDefault(Language $language): void
    {
        DB::transaction(function () use ($language): void {
            $this->repository->clearDefault();
            $this->repository->update($language, ['is_default' => true]);
        });

        $this->cache->flush();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Language
    {
        $language = $this->repository->create($data);

        if (! empty($data['is_default'])) {
            $this->setDefault($language);
        }

        return $language;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Language $language, array $data): Language
    {
        $language = $this->repository->update($language, $data);

        if (! empty($data['is_default'])) {
            $this->setDefault($language);
        }

        $this->cache->flush($language->code);

        return $language;
    }

    public function delete(Language $language): void
    {
        if ($language->is_default) {
            throw new InvalidArgumentException('Cannot delete the default language.');
        }

        $this->repository->delete($language);
        $this->cache->flush($language->code);
    }

    public function findOrFail(int $id): Language
    {
        return $this->repository->findOrFail($id);
    }
}
