<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private function authPayload(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'rol' => $user->rol,
            ],
        ];
    }

    private function assignUserRole(int $userId): void
    {
        $roleId = DB::table('roles')->where('name', 'USER')->value('id');
        if (!$roleId) {
            return;
        }

        DB::table('users_roles')->where('user_id', $userId)->delete();
        DB::table('users_roles')->insert([
            'user_id' => $userId,
            'rol_id' => $roleId,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $identifier = $request->string('email')->toString();

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user || !Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas', 'details' => null], 401);
        }

        return response()->json($this->authPayload($user));
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $userRoleId = DB::table('roles')->where('name', 'USER')->value('id');
        if (!$userRoleId) {
            return response()->json(['message' => 'Rol USER no configurado', 'details' => null], 500);
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $this->assignUserRole($user->id);

            return $user;
        });

        return response()->json($this->authPayload($user), 201);
    }
}
