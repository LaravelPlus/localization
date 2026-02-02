<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use LaravelPlus\Localization\Contracts\LanguageServiceInterface;
use LaravelPlus\Localization\Contracts\TranslationServiceInterface;
use LaravelPlus\Localization\Http\Requests\StoreTranslationRequest;
use LaravelPlus\Localization\Http\Requests\UpdateTranslationRequest;
use LaravelPlus\Localization\Models\Translation;

final class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationServiceInterface $translationService,
        private readonly LanguageServiceInterface $languageService,
    ) {}

    /**
     * Display a listing of translations.
     */
    public function index(Request $request): Response
    {
        $languageId = $request->filled('language_id') ? (int) $request->get('language_id') : null;
        $group = $request->filled('group') ? $request->get('group') : null;
        $search = $request->filled('search') ? $request->get('search') : null;

        return Inertia::render('admin/Localizations/Translations/Index', [
            'translations' => $this->translationService->paginate($languageId, $group, $search),
            'languages' => $this->languageService->getActive(),
            'groups' => $this->translationService->getAllGroups(),
            'status' => $request->session()->get('status'),
            'filters' => [
                'search' => $request->get('search', ''),
                'language_id' => $request->get('language_id', ''),
                'group' => $request->get('group', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new translation.
     */
    public function create(): Response
    {
        return Inertia::render('admin/Localizations/Translations/Create', [
            'languages' => $this->languageService->getActive(),
            'groups' => $this->translationService->getAllGroups(),
        ]);
    }

    /**
     * Store a newly created translation.
     */
    public function store(StoreTranslationRequest $request): RedirectResponse
    {
        $this->translationService->create($request->validated());

        return redirect()->route('admin.localizations.translations.index')
            ->with('status', 'Translation created successfully.');
    }

    /**
     * Show the form for editing a translation.
     */
    public function edit(Translation $translation): Response
    {
        $translation->load('language');

        return Inertia::render('admin/Localizations/Translations/Edit', [
            'translation' => $translation,
            'languages' => $this->languageService->getActive(),
            'groups' => $this->translationService->getAllGroups(),
        ]);
    }

    /**
     * Update the specified translation.
     */
    public function update(UpdateTranslationRequest $request, Translation $translation): RedirectResponse
    {
        $this->translationService->update($translation, $request->validated());

        return redirect()->route('admin.localizations.translations.index')
            ->with('status', 'Translation updated successfully.');
    }

    /**
     * Remove the specified translation.
     */
    public function destroy(Translation $translation): RedirectResponse
    {
        $this->translationService->delete($translation);

        return redirect()->route('admin.localizations.translations.index')
            ->with('status', 'Translation deleted successfully.');
    }
}
