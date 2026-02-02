<?php

declare(strict_types=1);

namespace LaravelPlus\Localization\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use LaravelPlus\Localization\Http\Requests\Admin\UpdateLocalizationSettingsRequest;
use LaravelPlus\Localization\Services\LocalizationSettingsService;

final class LocalizationSettingsController
{
    public function __construct(
        private(set) LocalizationSettingsService $settingsService,
    ) {}

    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        if (!$user || !array_any(['super-admin', 'admin'], fn (string $role): bool => $user->hasRole($role))) {
            abort(403, 'Unauthorized. Admin access required.');
        }
    }

    public function index(): Response
    {
        $this->authorizeAdmin();

        return Inertia::render('admin/Localizations/Settings', [
            'settings' => $this->settingsService->getAll(),
        ]);
    }

    public function update(UpdateLocalizationSettingsRequest $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $this->settingsService->update($request->validated());

        if (class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log('localization_settings.updated', null, null, $request->validated());
        }

        return redirect()->route('admin.localizations.settings')
            ->with('status', 'Localization settings updated successfully.');
    }
}
