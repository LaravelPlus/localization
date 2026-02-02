<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Resolvers;

use Illuminate\Http\Request;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;

final class UserPreferenceResolver implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $column = config('localization.detection.user_column', 'locale');

        return $user->{$column} ?? null;
    }
}
