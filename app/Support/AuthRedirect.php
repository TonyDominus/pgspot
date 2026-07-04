<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AuthRedirect
{
    public static function intended(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('home', absolute: false));
    }
}
