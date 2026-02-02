<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaravelPlus\Localization\Models\Language;
use LaravelPlus\Localization\Models\Translation;

/**
 * @extends Factory<Translation>
 */
final class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'language_id' => Language::factory(),
            'group' => fake()->randomElement(['messages', 'validation', 'auth', '*']),
            'key' => fake()->unique()->slug(2),
            'value' => fake()->sentence(),
        ];
    }

    public function forLanguage(Language $language): static
    {
        return $this->state(fn (): array => [
            'language_id' => $language->id,
        ]);
    }
}
