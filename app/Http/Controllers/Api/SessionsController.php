<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sessions\CreateSessionRequest;
use App\Http\Requests\Sessions\SessionQueryRequest;
use App\Http\Requests\Sessions\UpdateSessionRequest;
use App\Models\Language;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;

class SessionsController extends Controller
{
    private function formatStartTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }

    private function normalizeSessionData(array $data): array
    {
        if (!isset($data['language_id'])) {
            return $data;
        }

        $language = Language::query()->find($data['language_id']);
        if ($language && $language->code === Sesion::PRIMARY_LANGUAGE_CODE) {
            $data['subtitles'] = null;
        }

        return $data;
    }

    private function mapSession(Sesion $session): array
    {
        return [
            'id' => $session->id,
            'movie_id' => $session->movie_id,
            'room_id' => $session->room_id,
            'language_id' => $session->language_id,
            'language_code' => $session->language?->code,
            'language_name' => $session->language?->name,
            'session_type' => $session->session_type,
            'subtitles' => $session->subtitles,
            'start_date' => $session->start_date?->format('Y-m-d'),
            'start_time' => $this->formatStartTime($session->start_time),
            'movie_title' => $session->pelicula?->title,
            'room_name' => $session->sala?->name,
        ];
    }

    public function index(SessionQueryRequest $request)
    {
        $movieId = $request->validated()['movieId'] ?? null;
        $query = Sesion::query()
            ->with(['pelicula:id,title', 'sala:id,name', 'language:id,code,name'])
            ->orderBy('start_date')
            ->orderBy('start_time');

        if ($movieId) {
            $query->where('movie_id', $movieId);
        }

        $sessions = $query->get()->map(fn (Sesion $session) => $this->mapSession($session));

        return response()->json($sessions);
    }

    public function show(int $id)
    {
        $session = Sesion::query()
            ->with(['pelicula:id,title', 'sala:id,name', 'language:id,code,name'])
            ->find($id);

        if (!$session) {
            return response()->json(['message' => 'Sesión no encontrada', 'details' => null], 404);
        }

        return response()->json($this->mapSession($session));
    }

    public function store(CreateSessionRequest $request)
    {
        $data = $this->normalizeSessionData($request->validated());
        if (!Pelicula::query()->whereKey($data['movie_id'])->exists()) {
            return response()->json(['message' => 'No existe la película indicada', 'details' => null], 404);
        }
        if (!Sala::query()->whereKey($data['room_id'])->exists()) {
            return response()->json(['message' => 'No existe la sala indicada', 'details' => null], 404);
        }

        $session = Sesion::query()->create($data);
        $session->load(['pelicula:id,title', 'sala:id,name', 'language:id,code,name']);

        return response()->json($this->mapSession($session), 201);
    }

    public function update(UpdateSessionRequest $request, int $id)
    {
        $session = Sesion::query()->find($id);
        if (!$session) {
            return response()->json(['message' => 'Sesión no encontrada', 'details' => null], 404);
        }

        $data = $this->normalizeSessionData($request->validated());

        if (isset($data['movie_id']) && !Pelicula::query()->whereKey($data['movie_id'])->exists()) {
            return response()->json(['message' => 'No existe la película indicada', 'details' => null], 404);
        }
        if (isset($data['room_id']) && !Sala::query()->whereKey($data['room_id'])->exists()) {
            return response()->json(['message' => 'No existe la sala indicada', 'details' => null], 404);
        }

        $session->fill($data)->save();
        $session->load(['pelicula:id,title', 'sala:id,name', 'language:id,code,name']);

        return response()->json($this->mapSession($session));
    }

    public function destroy(int $id)
    {
        $session = Sesion::query()->find($id);
        if (!$session) {
            return response()->json(['message' => 'Sesión no encontrada', 'details' => null], 404);
        }

        $session->delete();

        return response()->noContent();
    }
}
