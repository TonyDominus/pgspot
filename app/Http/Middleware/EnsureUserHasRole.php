<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  UserRole|UserRole[]  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Accesso non autorizzato.');
        }

        $requiredRoles = collect($roles)
            ->flatMap(fn (string $role) => explode(',', $role))
            ->map(fn (string $role) => UserRole::from(trim($role)))
            ->all();

        foreach ($requiredRoles as $requiredRole) {
            if ($user->role->isAtLeast($requiredRole)) {
                return $next($request);
            }
        }

        abort(403, 'Non hai i permessi necessari.');
    }
}
