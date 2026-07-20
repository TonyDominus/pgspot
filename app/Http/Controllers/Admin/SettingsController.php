<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => [
                'tagline' => AppSetting::getValue('app.tagline', 'La mappa collaborativa di Perugia'),
                'map_center' => AppSetting::getValue('app.default_center', ['lat' => 43.1107, 'lng' => 12.3908, 'zoom' => 14]),
                'paypal_url' => AppSetting::getValue('site.paypal', ['url' => ''])['url'] ?? '',
                'instagram' => AppSetting::getValue('site.social', [])['instagram'] ?? '',
                'facebook' => AppSetting::getValue('site.social', [])['facebook'] ?? '',
                'contact_email' => AppSetting::getValue('site.contact', [])['email'] ?? 'info@pgspot.it',
                'legal_privacy' => $this->legalBody('legal.privacy'),
                'legal_terms' => $this->legalBody('legal.terms'),
                'legal_cookies' => $this->legalBody('legal.cookies'),
                'legal_contact' => $this->legalBody('legal.contact'),
                'events_public' => (bool) AppSetting::getValue('features.events_public', true),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tagline' => 'required|string|max:255',
            'map_lat' => 'required|numeric|between:-90,90',
            'map_lng' => 'required|numeric|between:-180,180',
            'map_zoom' => 'required|integer|between:1,19',
            'paypal_url' => 'nullable|url|max:500',
            'instagram' => 'nullable|url|max:500',
            'facebook' => 'nullable|url|max:500',
            'contact_email' => 'required|email|max:255',
            'legal_privacy' => 'nullable|string|max:50000',
            'legal_terms' => 'nullable|string|max:50000',
            'legal_cookies' => 'nullable|string|max:50000',
            'legal_contact' => 'nullable|string|max:50000',
            'events_public' => 'boolean',
        ]);

        AppSetting::setValue('app.tagline', $validated['tagline']);
        AppSetting::setValue('app.default_center', [
            'lat' => (float) $validated['map_lat'],
            'lng' => (float) $validated['map_lng'],
            'zoom' => (int) $validated['map_zoom'],
        ]);
        AppSetting::setValue('site.paypal', ['url' => $validated['paypal_url'] ?? '']);
        AppSetting::setValue('site.social', [
            'instagram' => $validated['instagram'] ?? '',
            'facebook' => $validated['facebook'] ?? '',
        ]);
        AppSetting::setValue('site.contact', ['email' => $validated['contact_email']]);

        AppSetting::setValue('legal.privacy', ['body' => $validated['legal_privacy'] ?? '']);
        AppSetting::setValue('legal.terms', ['body' => $validated['legal_terms'] ?? '']);
        AppSetting::setValue('legal.cookies', ['body' => $validated['legal_cookies'] ?? '']);
        AppSetting::setValue('legal.contact', ['body' => $validated['legal_contact'] ?? '']);
        AppSetting::setValue('features.events_public', $request->boolean('events_public'));

        return back()->with('success', 'Impostazioni salvate.');
    }

    private function legalBody(string $key): string
    {
        $raw = AppSetting::getValue($key);

        return is_array($raw) ? ($raw['body'] ?? '') : (string) ($raw ?? '');
    }
}
