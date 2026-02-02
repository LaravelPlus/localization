<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelPlus\Localization\Repositories\LanguageRepository;

final class LocaleSwitchController extends Controller
{
    public function __construct(private readonly LanguageRepository $languageRepository) {}

    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $language = $this->languageRepository->findByCode($code);

        if ($language === null || ! $language->is_active) {
            return redirect()->back();
        }

        $request->session()->put('locale', $code);

        $user = $request->user();
        $column = config('localization.detection.user_column', 'locale');

        if ($user !== null && $user->isFillable($column)) {
            $user->update([$column => $code]);
        }

        return redirect()->back();
    }
}
