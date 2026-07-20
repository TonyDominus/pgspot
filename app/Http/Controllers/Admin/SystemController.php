<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\TestMailNotification;
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
        $request->user()->notify(new TestMailNotification);

        return back()->with('success', 'Email di test inviata a '.$request->user()->email);
    }
}
