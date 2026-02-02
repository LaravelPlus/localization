<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function __construct(private readonly LocaleResolverInterface $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolver->resolve($request);

        if ($locale !== null) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
