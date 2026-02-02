<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('localization.tables.content_translations', 'content_translations'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('translatable');
            $table->foreignId('language_id')
                ->constrained(config('localization.tables.languages', 'languages'))
                ->cascadeOnDelete();
            $table->string('field');
            $table->text('value');
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'language_id', 'field'], 'content_translations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('localization.tables.content_translations', 'content_translations'));
    }
};
