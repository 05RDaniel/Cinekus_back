<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado para este token', 'details' => null], 401);
            }
            $request->attributes->set('auth_user', $user);
        } catch (JWTException $exception) {
            return response()->json(['message' => 'Token invalido o expirado', 'details' => null], 401);
        }

        return $next($request);
    }
}
