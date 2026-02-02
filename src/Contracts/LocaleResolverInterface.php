<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Contracts;

use Illuminate\Http\Request;

interface LocaleResolverInterface
{
    /**
     * Resolve the locale from the request.
     */
    public function resolve(Request $request): ?string;
}
