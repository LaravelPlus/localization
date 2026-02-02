<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Resolvers;

use Illuminate\Http\Request;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;

final class ChainResolver implements LocaleResolverInterface
{
    /**
     * @var array<int, LocaleResolverInterface>
     */
    private readonly array $resolvers;

    /**
     * @param  array<int, LocaleResolverInterface>  $resolvers
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function resolve(Request $request): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $locale = $resolver->resolve($request);

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }
}
