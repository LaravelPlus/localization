<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use LaravelPlus\Localization\Models\Translation;

final class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $languagesTable = config('localization.tables.languages', 'languages');

        return [
            'language_id' => ['required', 'integer', "exists:{$languagesTable},id"],
            'group' => ['required', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $translation = $this->route('translation');
                    $exists = Translation::query()
                        ->where('language_id', $this->input('language_id'))
                        ->where('group', $this->input('group'))
                        ->where('key', $value)
                        ->where('id', '!=', $translation->id)
                        ->exists();

                    if ($exists) {
                        $fail('A translation with this key already exists for the selected language and group.');
                    }
                },
            ],
            'value' => ['required', 'string'],
        ];
    }
}
