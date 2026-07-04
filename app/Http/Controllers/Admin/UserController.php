<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->q.'%')
                    ->orWhere('email', 'like', '%'.$request->q.'%');
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['q', 'role']),
            'roles' => collect(UserRole::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:'.implode(',', array_column(UserRole::cases(), 'value')),
            'is_trusted_contributor' => 'boolean',
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== UserRole::SuperAdmin->value) {
            return back()->with('error', 'Non puoi rimuovere il tuo ruolo superadmin.');
        }

        $user->update([
            ...$validated,
            'is_trusted_contributor' => $request->boolean('is_trusted_contributor'),
        ]);

        return back()->with('success', 'Utente aggiornato.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Non puoi eliminare il tuo account da qui.');
        }

        $user->delete();

        return back()->with('success', 'Utente eliminato.');
    }
}
