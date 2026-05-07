<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Movies\CreateMovieRequest;
use App\Http\Requests\Movies\UpdateMovieRequest;
use App\Models\Pelicula;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MoviesController extends Controller
{
    private function tmdbClient(array $headers): PendingRequest
    {
        $disableSslVerify = filter_var(env('TMDB_DISABLE_SSL_VERIFY', false), FILTER_VALIDATE_BOOL);
        $client = Http::withHeaders($headers);
        if ($disableSslVerify) {
            $client = $client->withOptions(['verify' => false]);
        }
        return $client;
    }

    public function index()
    {
        return response()->json(Pelicula::query()->orderByDesc('id')->get());
    }

    public function show(int $id)
    {
        $movie = Pelicula::query()->find($id);
        if (!$movie) {
            return response()->json(['message' => 'Pelicula no encontrada', 'details' => null], 404);
        }
        return response()->json($movie);
    }

    public function store(CreateMovieRequest $request)
    {
        $movie = Pelicula::query()->create($request->validated());
        return response()->json($movie, 201);
    }

    public function update(UpdateMovieRequest $request, int $id)
    {
        $movie = Pelicula::query()->find($id);
        if (!$movie) {
            return response()->json(['message' => 'Pelicula no encontrada para actualizar', 'details' => null], 404);
        }
        $movie->fill($request->validated())->save();
        return response()->json($movie);
    }

    public function destroy(int $id)
    {
        $movie = Pelicula::query()->find($id);
        if (!$movie) {
            return response()->json(['message' => 'Pelicula no encontrada para eliminar', 'details' => null], 404);
        }
        $movie->delete();
        return response('', 204);
    }

    public function randomPopular()
    {
        $limit = max((int) request('limit', 3), 1);
        $language = request('language', 'es-ES');
        $headers = ['accept' => 'application/json'];

        $readToken = env('TMDB_READ_ACCESS_TOKEN');
        $apiKey = env('TMDB_API_KEY');
        if ($readToken) {
            $headers['Authorization'] = "Bearer {$readToken}";
        } elseif (!$apiKey) {
            return response()->json(['message' => 'TMDB no configurado en variables de entorno', 'details' => null], 500);
        }

        $base = "https://api.themoviedb.org/3/movie/popular?language={$language}&page=1";
        $genresBase = "https://api.themoviedb.org/3/genre/movie/list?language={$language}";
        if (!$readToken && $apiKey) {
            $base .= '&api_key='.urlencode($apiKey);
            $genresBase .= '&api_key='.urlencode($apiKey);
        }

        try {
            $popularResponse = $this->tmdbClient($headers)->get($base);
        } catch (ConnectionException) {
            return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
        }
        if (!$popularResponse->ok()) {
            return response()->json(['message' => 'No se pudo obtener peliculas populares de TMDB', 'details' => null], 502);
        }

        $genreResponse = $this->tmdbClient($headers)->get($genresBase);
        $genreMap = collect($genreResponse->json('genres', []))->mapWithKeys(fn ($g) => [$g['id'] => $g['name']]);

        $results = collect($popularResponse->json('results', []))
            ->filter(fn ($movie) => !empty($movie['poster_path']))
            ->shuffle()
            ->take($limit)
            ->values()
            ->map(function ($movie) use ($genreMap) {
                return [
                    'id' => $movie['id'],
                    'title' => $movie['title'] ?? $movie['name'] ?? 'Sin titulo',
                    'image' => "https://image.tmdb.org/t/p/w500{$movie['poster_path']}",
                    'genres' => collect($movie['genre_ids'] ?? [])->map(fn ($id) => $genreMap->get($id, (string) $id))->values(),
                    'vote_average' => (float) ($movie['vote_average'] ?? 0),
                    'overview' => $movie['overview'] ?? '',
                ];
            });

        return response()->json($results);
    }

    public function allTmdb()
    {
        $language = request('language', 'es-ES');
        $maxPages = max(1, min((int) request('maxPages', 20), 500));
        $headers = ['accept' => 'application/json'];
        $readToken = env('TMDB_READ_ACCESS_TOKEN');
        $apiKey = env('TMDB_API_KEY');
        if ($readToken) {
            $headers['Authorization'] = "Bearer {$readToken}";
        } elseif (!$apiKey) {
            return response()->json(['message' => 'TMDB no configurado en variables de entorno', 'details' => null], 500);
        }

        $genreUrl = "https://api.themoviedb.org/3/genre/movie/list?language={$language}";
        if (!$readToken && $apiKey) {
            $genreUrl .= '&api_key='.urlencode($apiKey);
        }
        try {
            $genreResponse = $this->tmdbClient($headers)->get($genreUrl);
        } catch (ConnectionException) {
            return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
        }
        $genreMap = collect($genreResponse->json('genres', []))->mapWithKeys(fn ($g) => [$g['id'] => $g['name']]);

        $movies = collect();
        $totalPages = 1;
        for ($page = 1; $page <= $maxPages && $page <= $totalPages; $page++) {
            $url = "https://api.themoviedb.org/3/movie/popular?language={$language}&page={$page}";
            if (!$readToken && $apiKey) {
                $url .= '&api_key='.urlencode($apiKey);
            }
            try {
                $response = $this->tmdbClient($headers)->get($url);
            } catch (ConnectionException) {
                return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
            }
            if (!$response->ok()) {
                return response()->json(['message' => 'No se pudieron obtener peliculas de TMDB', 'details' => null], 502);
            }
            $totalPages = (int) ($response->json('total_pages') ?? 1);
            $movies = $movies->concat(
                collect($response->json('results', []))->map(function ($movie) use ($genreMap) {
                    return [
                        'id' => $movie['id'],
                        'title' => $movie['title'] ?? $movie['name'] ?? 'Sin titulo',
                        'image' => empty($movie['poster_path']) ? null : "https://image.tmdb.org/t/p/w500{$movie['poster_path']}",
                        'genres' => collect($movie['genre_ids'] ?? [])->map(fn ($id) => $genreMap->get($id, (string) $id))->values(),
                        'vote_average' => (float) ($movie['vote_average'] ?? 0),
                        'overview' => $movie['overview'] ?? '',
                    ];
                })
            );
        }

        return response()->json([
            'total' => $movies->count(),
            'fetchedPages' => min($maxPages, $totalPages),
            'totalPages' => $totalPages,
            'movies' => $movies->values(),
        ]);
    }
}
