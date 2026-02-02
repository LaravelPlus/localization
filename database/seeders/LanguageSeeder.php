<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Database\Seeders;

use Illuminate\Database\Seeder;
use LaravelPlus\Localization\Models\Language;

final class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true, 'is_active' => true, 'sort_order' => 1],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 2],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 3],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 4],
            ['code' => 'sl', 'name' => 'Slovenian', 'native_name' => 'Slovenščina', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 5],
            ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 6],
            ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 7],
            ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 8],
            ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'direction' => 'ltr', 'is_default' => false, 'is_active' => true, 'sort_order' => 9],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_default' => false, 'is_active' => true, 'sort_order' => 10],
        ];

        foreach ($languages as $language) {
            Language::query()->updateOrCreate(
                ['code' => $language['code']],
                $language,
            );
        }
    }
}
