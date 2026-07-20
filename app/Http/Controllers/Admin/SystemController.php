<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Notifications\TestMailNotification;
use App\Support\SafeMail;
use App\Services\SystemHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function __construct(private SystemHealthService $health) {}

    public function index(): Response
    {
        return Inertia::render('Admin/System/Index', [
            'health' => $this->health->snapshot(),
        ]);
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        if (! SafeMail::send($request->user(), new TestMailNotification)) {
            $error = SafeMail::lastError()['message'] ?? 'Errore sconosciuto';

            return back()->with('error', 'Invio fallito: '.$error);
        }

        AppSetting::setValue('system.last_test_mail', [
            'at' => now()->toIso8601String(),
            'to' => $request->user()->email,
        ]);

        return back()->with('success', 'Email di test inviata a '.$request->user()->email);
    }
}
