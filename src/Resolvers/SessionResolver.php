<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Resolvers;

use Illuminate\Http\Request;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;

final class SessionResolver implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        return $request->session()->get('locale');
    }
}
