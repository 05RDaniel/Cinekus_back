<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!filter_var(config('auth.auth_checks_enabled', false), FILTER_VALIDATE_BOOL)) {
            return $next($request);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado', 'details' => null], 401);
        }

        $request->attributes->set('auth_user', $user);

        if (filter_var(config('auth.role_checks_enabled', false), FILTER_VALIDATE_BOOL)) {
            if (!in_array($user->rol, $roles, true)) {
                return response()->json(['message' => 'No tienes permisos para esta acción', 'details' => null], 403);
            }
        }

        return $next($request);
    }
}
