<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'platformFeePercent' => (float) Setting::getValue('platform_fee_percent', 0),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::setValue(
            'platform_fee_percent',
            number_format((float) $validated['platform_fee_percent'], 2, '.', '')
        );

        return back()->with('status', 'Platform fee updated.');
    }
}
