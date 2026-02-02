<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Resolvers;

use Illuminate\Http\Request;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;
use LaravelPlus\Localization\Models\Language;

final class UrlPrefixResolver implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $segment = $request->segment(1);

        if ($segment === null) {
            return null;
        }

        $exists = Language::query()
            ->where('code', $segment)
            ->where('is_active', true)
            ->exists();

        return $exists ? $segment : null;
    }
}
