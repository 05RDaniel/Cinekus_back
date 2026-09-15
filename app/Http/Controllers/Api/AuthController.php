<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Support\UniqueSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private function authPayload(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token,
            'user' => $this->userPayload($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'second_last_name' => $user->second_last_name,
            'email' => $user->email,
            'rol' => $user->rol,
        ];
    }

    private function currentUser(Request $request): ?User
    {
        $user = $request->attributes->get('auth_user') ?? auth('api')->user();

        return $user instanceof User ? $user : null;
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
            $email = $data['email'];
            $user = User::query()->create([
                'username' => UniqueSlug::make(
                    'users',
                    'username',
                    $data['first_name'].' '.$data['last_name'],
                    Str::before($email, '@')
                ),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'second_last_name' => isset($data['second_last_name']) ? trim((string) $data['second_last_name']) ?: null : null,
                'email' => $email,
                'password' => $data['password'],
            ]);
            $this->assignUserRole($user->id);

            return $user;
        });

        return response()->json($this->authPayload($user), 201);
    }

    public function me(Request $request)
    {
        $user = $this->currentUser($request);
        if (!$user) {
            return response()->json(['message' => 'Debes iniciar sesión', 'details' => null], 401);
        }

        return response()->json($this->userPayload($user));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $this->currentUser($request);
        if (!$user) {
            return response()->json(['message' => 'Debes iniciar sesión', 'details' => null], 401);
        }

        $data = $request->validated();
        $user->first_name = trim($data['first_name']);
        $user->last_name = trim($data['last_name']);
        $user->second_last_name = isset($data['second_last_name']) ? trim((string) $data['second_last_name']) ?: null : null;
        $user->email = strtolower(trim($data['email']));
        $user->save();

        return response()->json($this->userPayload($user->fresh() ?? $user));
    }
}
