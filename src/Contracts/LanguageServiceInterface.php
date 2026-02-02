<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelPlus\Localization\Models\Language;

interface LanguageServiceInterface
{
    /**
     * @return LengthAwarePaginator<Language>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, Language>
     */
    public function getActive(): Collection;

    public function getDefault(): ?Language;

    public function setDefault(Language $language): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Language;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Language $language, array $data): Language;

    public function delete(Language $language): void;

    public function findOrFail(int $id): Language;
}
