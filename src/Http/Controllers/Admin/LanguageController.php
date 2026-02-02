<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use LaravelPlus\Localization\Contracts\LanguageServiceInterface;
use LaravelPlus\Localization\Database\Seeders\LanguageSeeder;
use LaravelPlus\Localization\Http\Requests\StoreLanguageRequest;
use LaravelPlus\Localization\Http\Requests\UpdateLanguageRequest;
use LaravelPlus\Localization\Models\Language;

final class LanguageController extends Controller
{
    public function __construct(private readonly LanguageServiceInterface $languageService) {}

    /**
     * Display a listing of languages.
     */
    public function index(Request $request): Response
    {
        $search = $request->filled('search') ? $request->get('search') : null;

        return Inertia::render('admin/Localizations/Languages/Index', [
            'languages' => $this->languageService->paginate($search),
            'fallbackLocale' => config('app.fallback_locale', 'en'),
            'status' => $request->session()->get('status'),
            'filters' => [
                'search' => $request->get('search', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new language.
     */
    public function create(): Response
    {
        return Inertia::render('admin/Localizations/Languages/Create');
    }

    /**
     * Store a newly created language.
     */
    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $this->languageService->create($request->validated());

        return redirect()->route('admin.localizations.languages.index')
            ->with('status', 'Language created successfully.');
    }

    /**
     * Show the form for editing a language.
     */
    public function edit(Language $language): Response
    {
        return Inertia::render('admin/Localizations/Languages/Edit', [
            'language' => $language,
        ]);
    }

    /**
     * Update the specified language.
     */
    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $this->languageService->update($language, $request->validated());

        return redirect()->route('admin.localizations.languages.index')
            ->with('status', 'Language updated successfully.');
    }

    /**
     * Remove the specified language.
     */
    public function destroy(Language $language): RedirectResponse
    {
        try {
            $this->languageService->delete($language);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.localizations.languages.index')
                ->withErrors(['language' => $e->getMessage()]);
        }

        return redirect()->route('admin.localizations.languages.index')
            ->with('status', 'Language deleted successfully.');
    }

    /**
     * Set a language as the default.
     */
    public function setDefault(Language $language): RedirectResponse
    {
        $this->languageService->setDefault($language);

        return redirect()->route('admin.localizations.languages.index')
            ->with('status', "Language \"{$language->name}\" set as default.");
    }

    /**
     * Seed common languages.
     */
    public function seedCommon(): RedirectResponse
    {
        (new LanguageSeeder())->run();

        return redirect()->route('admin.localizations.languages.index')
            ->with('status', 'Common languages have been added.');
    }
}
