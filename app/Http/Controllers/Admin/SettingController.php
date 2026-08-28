<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Homepage section management (§35): hero copy, section visibility. */
class SettingController extends Controller
{
    public function updateHomepage(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $data = $request->validate([
            'hero_title' => 'required|string|max:120',
            'hero_subtitle' => 'required|string|max:240',
            'show_deals' => 'boolean',
            'show_price_drops' => 'boolean',
            'show_trending' => 'boolean',
            'banner_note' => 'nullable|string|max:200',
        ]);

        Setting::put('homepage', $data);
        cache()->forget('home.sections');

        return back()->with('status', 'Homepage settings saved.');
    }
}
