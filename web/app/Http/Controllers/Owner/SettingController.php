<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFeeSettingRequest;
use App\Services\PlatformSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private PlatformSettingService $settingService
    ) {}

    /**
     * Display platform settings page.
     */
    public function index(): View
    {
        $settings = $this->settingService->getFeeSettings();
        $preview = $this->settingService->getFeePreview();

        return view('owner.settings.index', compact('settings', 'preview'));
    }

    /**
     * Update fee settings.
     */
    public function update(UpdateFeeSettingRequest $request): RedirectResponse
    {
        try {
            $this->settingService->updateFeeSettings($request->validated());

            return redirect()
                ->route('owner.settings.index')
                ->with('success', 'Pengaturan fee berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui pengaturan: ' . $e->getMessage());
        }
    }
}
