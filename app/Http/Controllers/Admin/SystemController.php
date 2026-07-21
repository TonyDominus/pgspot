<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Notifications\TestMailNotification;
use App\Support\SafeMail;
use App\Services\SmokeTestService;
use App\Services\SystemHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function __construct(
        private SystemHealthService $health,
        private SmokeTestService $smoke,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/System/Index', [
            'health' => $this->health->snapshot(),
            'smokeTest' => SmokeTestService::lastResult(),
            'uptimeUrl' => url('/up'),
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

    public function runSmokeTest(): RedirectResponse
    {
        $result = $this->smoke->run();

        $message = $result['ok']
            ? "Smoke test ok ({$result['passed']} pass, {$result['warnings']} avvisi)."
            : "Smoke test fallito: {$result['failed']} errori, {$result['warnings']} avvisi.";

        return back()->with($result['ok'] ? 'success' : 'error', $message);
    }
}
