<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado', 'details' => null], 401);
        }

        if (!in_array($user->rol, $roles, true)) {
            return response()->json(['message' => 'No tienes permisos para esta accion', 'details' => null], 403);
        }

        return $next($request);
    }
}
