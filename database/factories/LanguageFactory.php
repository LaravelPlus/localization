<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaravelPlus\Localization\Models\Language;

/**
 * @extends Factory<Language>
 */
final class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->languageCode(),
            'name' => fake()->word(),
            'native_name' => fake()->word(),
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function rtl(): static
    {
        return $this->state(fn (): array => [
            'direction' => 'rtl',
        ]);
    }
}
