<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at,
                    'role' => $request->user()->role->value,
                    'is_trusted_contributor' => $request->user()->is_trusted_contributor,
                    'notify_contributions' => $request->user()->notify_contributions,
                    'notify_poi_updates' => $request->user()->notify_poi_updates,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'favoritePoiIds' => fn () => $request->user()
                ? $request->user()->favoritePois()->pluck('id')->all()
                : [],
            'site' => fn () => [
                'paypalUrl' => AppSetting::getValue('site.paypal', ['url' => ''])['url'] ?? '',
                'instagram' => AppSetting::getValue('site.social', [])['instagram'] ?? '',
                'facebook' => AppSetting::getValue('site.social', [])['facebook'] ?? '',
                'email' => AppSetting::getValue('site.contact', [])['email'] ?? 'info@pgspot.it',
            ],
        ];
    }
}
