<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    private function userSelectQuery()
    {
        return DB::table('users as u')
            ->select([
                'u.id',
                'u.username',
                'u.email',
                DB::raw("COALESCE((
                    SELECT r.name
                    FROM users_roles ur
                    JOIN roles r ON r.id = ur.rol_id
                    WHERE ur.user_id = u.id
                    ORDER BY r.id
                    LIMIT 1
                ), 'USER') as rol"),
            ]);
    }

    private function resolveRoleId(string $roleName): ?int
    {
        return DB::table('roles')->where('name', $roleName)->value('id');
    }

    private function syncUserRole(int $userId, string $roleName): void
    {
        $roleId = $this->resolveRoleId($roleName);
        if (!$roleId) {
            return;
        }

        DB::table('users_roles')->where('user_id', $userId)->delete();
        DB::table('users_roles')->insert([
            'user_id' => $userId,
            'rol_id' => $roleId,
        ]);
    }

    public function index()
    {
        $users = $this->userSelectQuery()->orderBy('u.id')->get();

        return response()->json($users);
    }

    public function show(int $id)
    {
        $user = $this->userSelectQuery()->where('u.id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado', 'details' => null], 404);
        }

        return response()->json($user);
    }

    public function store(CreateUserRequest $request)
    {
        $data = $request->validated();
        $roleName = $data['rol'] ?? 'USER';

        if (!$this->resolveRoleId($roleName)) {
            return response()->json(['message' => "Rol no válido: {$roleName}", 'details' => null], 422);
        }

        $user = DB::transaction(function () use ($data, $roleName) {
            $user = User::query()->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $this->syncUserRole($user->id, $roleName);

            return $user;
        });

        return response()->json(
            $this->userSelectQuery()->where('u.id', $user->id)->first(),
            201
        );
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = User::query()->find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado', 'details' => null], 404);
        }

        $data = $request->validated();
        if (isset($data['rol']) && !$this->resolveRoleId($data['rol'])) {
            return response()->json(['message' => "Rol no válido: {$data['rol']}", 'details' => null], 422);
        }

        DB::transaction(function () use ($user, $data) {
            $fields = array_intersect_key($data, array_flip(['username', 'email', 'password']));
            if ($fields !== []) {
                $user->fill($fields)->save();
            }
            if (isset($data['rol'])) {
                $this->syncUserRole($user->id, $data['rol']);
            }
        });

        return $this->show($id);
    }

    public function destroy(int $id)
    {
        $user = User::query()->find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado', 'details' => null], 404);
        }

        $user->delete();

        return response()->noContent();
    }
}
