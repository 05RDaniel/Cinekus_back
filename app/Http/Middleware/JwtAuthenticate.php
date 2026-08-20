<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!filter_var(config('auth.auth_checks_enabled', false), FILTER_VALIDATE_BOOL)) {
            return $next($request);
        }

        try {
            $token = $request->bearerToken() ?? $request->query('token');
            if (!$token) {
                return response()->json(['message' => 'Token no proporcionado', 'details' => null], 401);
            }

            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user instanceof User) {
                return response()->json(['message' => 'Usuario no encontrado para este token', 'details' => null], 401);
            }

            $request->attributes->set('auth_user', $user);
            auth()->shouldUse('api');
            auth('api')->setUser($user);
        } catch (JWTException) {
            return response()->json(['message' => 'Token inválido o expirado', 'details' => null], 401);
        }

        return $next($request);
    }
}
