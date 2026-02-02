<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('localization.tables.translations', 'translations'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('language_id')
                ->constrained(config('localization.tables.languages', 'languages'))
                ->cascadeOnDelete();
            $table->string('group')->default('*');
            $table->string('key');
            $table->text('value');
            $table->timestamps();

            $table->unique(['language_id', 'group', 'key']);
            $table->index(['language_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('localization.tables.translations', 'translations'));
    }
};
