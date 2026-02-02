<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLocalizationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'static.driver' => ['required', 'string', 'in:file,database,hybrid'],
            'content.enabled' => ['required', 'boolean'],
            'content.strategy' => ['required', 'string', 'in:json_column,polymorphic_table'],
            'detection.strategy' => ['required', 'string', 'in:session,url_prefix,user_preference,browser,chain'],
            'detection.chain' => ['required', 'array'],
            'detection.chain.*' => ['required', 'string', 'in:session,url_prefix,user_preference,browser'],
            'detection.user_column' => ['required', 'string', 'max:255'],
            'cache.enabled' => ['required', 'boolean'],
            'cache.ttl' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'static.driver.in' => 'The translation driver must be file, database, or hybrid.',
            'content.strategy.in' => 'The content strategy must be json_column or polymorphic_table.',
            'detection.strategy.in' => 'The detection strategy must be session, url_prefix, user_preference, browser, or chain.',
            'detection.chain.*.in' => 'Each chain resolver must be session, url_prefix, user_preference, or browser.',
            'cache.ttl.min' => 'The cache TTL cannot be negative.',
        ];
    }
}
