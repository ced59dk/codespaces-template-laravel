<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFilamentActiveTenant
{
    /**
     * Vérifie qu'un utilisateur est actif et rattaché à un tenant avant d'accéder au panel Filament.
     *
     * Si l'utilisateur n'est pas connecté, n'est pas actif ou n'a pas de tenant_id,
     * retourne une réponse 403 Forbidden.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user || ! $user->active || ! $user->tenant_id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
