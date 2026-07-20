<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SafeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if (! SafeMail::sendVerification($request->user())) {
            return back()->with('error', 'Impossibile inviare l\'email. Verifica la configurazione Resend in Admin → Monitoraggio.');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
