<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'accept_terms' => 'accepted',
            'accept_privacy' => 'accepted',
            'notify_contributions' => 'boolean',
            'notify_poi_updates' => 'boolean',
        ], [
            'accept_terms.accepted' => 'Devi accettare i termini di utilizzo.',
            'accept_privacy.accepted' => 'Devi accettare l\'informativa privacy.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::User,
            'accepted_terms_at' => now(),
            'accepted_privacy_at' => now(),
            'notify_contributions' => $request->boolean('notify_contributions'),
            'notify_poi_updates' => $request->boolean('notify_poi_updates'),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
