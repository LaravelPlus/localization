<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelPlus\Localization\Database\Factories\LanguageFactory;

final class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'is_default',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('localization.tables.languages', 'languages');
    }

    /**
     * @return HasMany<Translation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    /**
     * @return HasMany<ContentTranslation, $this>
     */
    public function contentTranslations(): HasMany
    {
        return $this->hasMany(ContentTranslation::class);
    }

    protected static function newFactory(): LanguageFactory
    {
        return LanguageFactory::new();
    }
}
