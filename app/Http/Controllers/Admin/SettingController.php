<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Loan;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Setting::current(),
            'penaltyTypes' => Loan::penaltyTypes(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $settings = Setting::current();
        $old = $settings->only(array_keys($request->validated()));

        $settings->update($request->validated());

        AuditLogger::log('Settings Updated', $settings, [
            'old' => $old,
            'new' => $request->validated(),
        ], 'Settings');

        return redirect()->route('admin.settings.edit')->with('status', 'Settings updated.');
    }
}
