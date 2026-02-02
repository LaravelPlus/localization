<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Resolvers;

use Illuminate\Http\Request;
use LaravelPlus\Localization\Contracts\LocaleResolverInterface;
use LaravelPlus\Localization\Models\Language;

final class BrowserResolver implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        $preferredLocales = $this->parseAcceptLanguage($acceptLanguage);
        $activeCodes = Language::query()
            ->where('is_active', true)
            ->pluck('code')
            ->all();

        foreach ($preferredLocales as $locale) {
            if (in_array($locale, $activeCodes, true)) {
                return $locale;
            }

            // Try base language (e.g. "en" from "en-US")
            $base = explode('-', $locale)[0];
            if (in_array($base, $activeCodes, true)) {
                return $base;
            }
        }

        return null;
    }

    /**
     * Parse the Accept-Language header into a sorted list of locale codes.
     *
     * @return array<int, string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $locales = [];

        foreach (explode(',', $header) as $part) {
            $parts = explode(';', trim($part));
            $locale = trim($parts[0]);
            $quality = 1.0;

            if (isset($parts[1])) {
                $qPart = trim($parts[1]);
                if (str_starts_with($qPart, 'q=')) {
                    $quality = (float) substr($qPart, 2);
                }
            }

            $locales[] = ['locale' => $locale, 'quality' => $quality];
        }

        usort($locales, fn (array $a, array $b): int => $b['quality'] <=> $a['quality']);

        return array_column($locales, 'locale');
    }
}
