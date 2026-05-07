<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sessions\CreateSessionRequest;
use App\Http\Requests\Sessions\SessionQueryRequest;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;

class SessionsController extends Controller
{
    public function index(SessionQueryRequest $request)
    {
        $movieId = $request->validated()['peliculaId'] ?? null;
        $query = Sesion::query()
            ->with(['pelicula:id,titulo', 'sala:id,nombre'])
            ->orderBy('fecha')
            ->orderBy('hora');

        if ($movieId) {
            $query->where('pelicula_id', $movieId);
        }

        $sessions = $query->get()->map(function (Sesion $session) {
            return [
                'id' => $session->id,
                'pelicula_id' => $session->pelicula_id,
                'sala_id' => $session->sala_id,
                'fecha' => $session->fecha,
                'hora' => $session->hora,
                'pelicula_titulo' => $session->pelicula?->titulo,
                'sala_nombre' => $session->sala?->nombre,
            ];
        });

        return response()->json($sessions);
    }

    public function store(CreateSessionRequest $request)
    {
        $data = $request->validated();
        if (!Pelicula::query()->whereKey($data['pelicula_id'])->exists()) {
            return response()->json(['message' => 'No existe la pelicula indicada', 'details' => null], 404);
        }
        if (!Sala::query()->whereKey($data['sala_id'])->exists()) {
            return response()->json(['message' => 'No existe la sala indicada', 'details' => null], 404);
        }

        $session = Sesion::query()->create($data);
        return response()->json($session, 201);
    }
}
