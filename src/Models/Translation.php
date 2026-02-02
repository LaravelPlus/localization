<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelPlus\Localization\Database\Factories\TranslationFactory;

final class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_id',
        'group',
        'key',
        'value',
    ];

    public function getTable(): string
    {
        return config('localization.tables.translations', 'translations');
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    protected static function newFactory(): TranslationFactory
    {
        return TranslationFactory::new();
    }
}
