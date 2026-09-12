<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'site_name' => Setting::get('site_name', 'Laporan'),
            'site_description' => Setting::get('site_description', 'Aplikasi Pengolah Laporan'),
            'contact_email' => Setting::get('contact_email', ''),
            'footer_text' => Setting::get('footer_text', ''),
            'site_logo' => Setting::get('site_logo', ''),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email|max:255',
            'footer_text' => 'nullable|string|max:500',
            'site_logo' => 'nullable|image|mimes:jpeg,png,svg,webp|max:2048',
        ]);

        if ($request->hasFile('site_logo')) {
            $oldLogo = Setting::get('site_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('site_logo')->store('logos', 'public');
            $validated['site_logo'] = $path;
        } elseif ($request->input('remove_logo') === '1') {
            $oldLogo = Setting::get('site_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $validated['site_logo'] = '';
        } else {
            unset($validated['site_logo']);
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
