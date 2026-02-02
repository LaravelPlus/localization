<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use LaravelPlus\Localization\Models\ContentTranslation;
use LaravelPlus\Localization\Models\Language;

trait HasTranslations
{
    /**
     * Get the translatable attributes for the model.
     *
     * @return array<int, string>
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Get the content translations relationship.
     *
     * @return MorphMany<ContentTranslation, $this>
     */
    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    /**
     * Get a translation for a specific field and locale.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $strategy = config('localization.content.strategy', 'polymorphic_table');

        if ($strategy === 'json_column') {
            return $this->getJsonTranslation($field, $locale);
        }

        return $this->getPolymorphicTranslation($field, $locale);
    }

    /**
     * Set a translation for a specific field and locale.
     */
    public function setTranslation(string $field, string $locale, string $value): void
    {
        $strategy = config('localization.content.strategy', 'polymorphic_table');

        if ($strategy === 'json_column') {
            $this->setJsonTranslation($field, $locale, $value);

            return;
        }

        $this->setPolymorphicTranslation($field, $locale, $value);
    }

    /**
     * Get a translated value with fallback to the original attribute.
     */
    public function translate(string $field, ?string $locale = null): string
    {
        $translation = $this->getTranslation($field, $locale);

        return $translation ?? ($this->{$field} ?? '');
    }

    private function getJsonTranslation(string $field, string $locale): ?string
    {
        $translationsColumn = $field . '_translations';
        $translations = $this->{$translationsColumn} ?? [];

        return $translations[$locale] ?? null;
    }

    private function setJsonTranslation(string $field, string $locale, string $value): void
    {
        $translationsColumn = $field . '_translations';
        $translations = $this->{$translationsColumn} ?? [];
        $translations[$locale] = $value;
        $this->{$translationsColumn} = $translations;
        $this->save();
    }

    private function getPolymorphicTranslation(string $field, string $locale): ?string
    {
        $language = Language::query()
            ->where('code', $locale)
            ->where('is_active', true)
            ->first();

        if (! $language) {
            return null;
        }

        $translation = $this->contentTranslations()
            ->where('language_id', $language->id)
            ->where('field', $field)
            ->first();

        return $translation?->value;
    }

    private function setPolymorphicTranslation(string $field, string $locale, string $value): void
    {
        $language = Language::query()
            ->where('code', $locale)
            ->where('is_active', true)
            ->firstOrFail();

        $this->contentTranslations()->updateOrCreate(
            [
                'language_id' => $language->id,
                'field' => $field,
            ],
            [
                'value' => $value,
            ],
        );
    }
}
