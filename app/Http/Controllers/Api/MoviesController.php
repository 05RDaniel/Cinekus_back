<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Movies\CreateMovieRequest;
use App\Http\Requests\Movies\UpdateMovieRequest;
use App\Models\Language;
use App\Models\MovieTranslation;
use App\Models\Person;
use App\Models\Pelicula;
use App\Support\Tmdb\TmdbClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

class MoviesController extends Controller
{
    private bool $englishLanguageResolved = false;

    private ?Language $englishLanguageCache = null;

    public function __construct(private readonly TmdbClient $tmdb)
    {
    }

    private function englishLanguage(): ?Language
    {
        if (!$this->englishLanguageResolved) {
            $this->englishLanguageCache = Language::query()->where('code', 'en')->first();
            $this->englishLanguageResolved = true;
        }

        return $this->englishLanguageCache;
    }

    private function formatMovie(Pelicula $movie): array
    {
        $movie->loadMissing(['genres', 'translations.language', 'people']);

        $englishLanguage = $this->englishLanguage();
        $englishTranslation = $englishLanguage
            ? $movie->translations->firstWhere('language_id', $englishLanguage->id)
            : null;

        return [
            'id' => $movie->id,
            'title' => $movie->title,
            'sinopsis' => $movie->sinopsis,
            'duration' => $movie->duration,
            'release_year' => $movie->release_year,
            'image' => $movie->image,
            'trailer_url' => $movie->trailer_url,
            'rating' => $movie->rating,
            'genre_ids' => $movie->genres->pluck('id')->values(),
            'cast' => $movie->people
                ->sortBy([['department', 'asc'], ['name', 'asc']])
                ->values()
                ->map(fn (Person $person) => [
                    'id' => $person->id,
                    'name' => $person->name,
                    'department' => $person->department,
                ]),
            'translation_en' => $englishTranslation ? [
                'title' => $englishTranslation->title,
                'sinopsis' => $englishTranslation->sinopsis,
                'is_available' => $englishTranslation->is_available,
            ] : null,
        ];
    }

  /** @param array<string, mixed> $payload */
    private function syncRelations(Pelicula $movie, array $payload): void
    {
        if (array_key_exists('genre_ids', $payload)) {
            $movie->genres()->sync($payload['genre_ids'] ?? []);
        }

        if (array_key_exists('cast', $payload)) {
            $this->syncCast($movie, $payload['cast'] ?? []);
        }

        if (!array_key_exists('translation_en', $payload)) {
            return;
        }

        $englishLanguage = $this->englishLanguage();
        if (!$englishLanguage) {
            return;
        }

        $translationPayload = $payload['translation_en'];
        if ($translationPayload === null) {
            MovieTranslation::query()
                ->where('movie_id', $movie->id)
                ->where('language_id', $englishLanguage->id)
                ->delete();

            return;
        }

        $isAvailable = (bool) ($translationPayload['is_available'] ?? false);
        $title = trim((string) ($translationPayload['title'] ?? ''));
        $sinopsis = trim((string) ($translationPayload['sinopsis'] ?? ''));

        if (!$isAvailable && $title === '' && $sinopsis === '') {
            MovieTranslation::query()
                ->where('movie_id', $movie->id)
                ->where('language_id', $englishLanguage->id)
                ->delete();

            return;
        }

        MovieTranslation::query()->updateOrCreate(
            ['movie_id' => $movie->id, 'language_id' => $englishLanguage->id],
            [
                'title' => $title !== '' ? $title : $movie->title,
                'sinopsis' => $sinopsis !== '' ? $sinopsis : $movie->sinopsis,
                'is_available' => $isAvailable,
            ]
        );
    }

  /** @param array<int, array<string, mixed>> $cast */
    private function syncCast(Pelicula $movie, array $cast): void
    {
        $personIds = [];

        foreach ($cast as $entry) {
            $name = trim((string) ($entry['name'] ?? ''));
            $department = (string) ($entry['department'] ?? '');

            if ($name === '' || !in_array($department, Person::DEPARTMENTS, true)) {
                continue;
            }

            $person = Person::query()->firstOrCreate([
                'name' => $name,
                'department' => $department,
            ]);

            $personIds[] = $person->id;
        }

        $movie->people()->sync(array_values(array_unique($personIds)));
    }

    public function index()
    {
        $movies = Pelicula::query()
            ->with(['genres', 'translations.language', 'people'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Pelicula $movie) => $this->formatMovie($movie));

        return response()->json($movies);
    }

    public function show(int $id)
    {
        $movie = Pelicula::query()->find($id);
        if (!$movie) {
            return response()->json(['message' => 'Película no encontrada', 'details' => null], 404);
        }

        return response()->json($this->formatMovie($movie));
    }

    public function store(CreateMovieRequest $request)
    {
        $payload = $request->validated();
        $movie = Pelicula::query()->create([
            'title' => $payload['title'],
            'sinopsis' => $payload['sinopsis'] ?? null,
            'duration' => $payload['duration'] ?? null,
            'release_year' => $payload['release_year'] ?? null,
            'image' => $payload['image'] ?? null,
            'trailer_url' => $payload['trailer_url'] ?? null,
            'rating' => $payload['rating'] ?? null,
        ]);

        $this->syncRelations($movie, $payload);

        return response()->json($this->formatMovie($movie->fresh()), 201);
    }

    public function update(UpdateMovieRequest $request, int $id)
    {
        $movie = Pelicula::query()->find($id);
        if (!$movie) {
            return response()->json(['message' => 'Película no encontrada para actualizar', 'details' => null], 404);
        }

        $payload = $request->validated();
        $movieFields = array_intersect_key($payload, array_flip(['title', 'sinopsis', 'duration', 'release_year', 'image', 'trailer_url', 'rating']));
        if (count($movieFields) > 0) {
            $movie->fill($movieFields)->save();
        }

        $this->syncRelations($movie, $payload);

        return response()->json($this->formatMovie($movie->fresh()));
    }

    public function destroy(int $id)
    {
        $movie = Pelicula::query()->find($id);
        if (!$movie) {
            return response()->json(['message' => 'Película no encontrada para eliminar', 'details' => null], 404);
        }

        try {
            DB::transaction(function () use ($movie): void {
                $movie->sesiones()->delete();
                $movie->delete();
            });
        } catch (\Throwable) {
            return response()->json([
                'message' => 'No se pudo eliminar la película por dependencias en la base de datos',
                'details' => null,
            ], 409);
        }

        return response('', 204);
    }

    public function tmdbImport(int $tmdbId)
    {
        if (!$this->tmdb->configured()) {
            return response()->json(['message' => 'TMDB no configurado en variables de entorno', 'details' => null], 500);
        }

        try {
            $spanishResponse = $this->tmdb->client()->get($this->tmdb->url("/movie/{$tmdbId}", 'es-ES'));
            $englishResponse = $this->tmdb->client()->get($this->tmdb->url("/movie/{$tmdbId}", 'en-US'));
            $creditsResponse = $this->tmdb->client()->get($this->tmdb->url("/movie/{$tmdbId}/credits", 'es-ES'));
            $videosResponse = $this->tmdb->client()->get($this->tmdb->url("/movie/{$tmdbId}/videos", 'es-ES'));
        } catch (ConnectionException) {
            return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
        }

        if (!$spanishResponse->ok()) {
            return response()->json(['message' => 'No se pudo obtener la película de TMDB', 'details' => null], 404);
        }

        $spanish = $spanishResponse->json();
        $english = $englishResponse->ok() ? $englishResponse->json() : $spanish;
        $credits = $creditsResponse->ok() ? $creditsResponse->json() : [];
        $posterPath = $spanish['poster_path'] ?? $english['poster_path'] ?? null;
        $trailerUrl = $videosResponse->ok() ? $this->tmdb->trailerUrlFromVideos($videosResponse->json()) : null;

        $cast = collect($credits['cast'] ?? [])
            ->take(12)
            ->map(fn ($member) => [
                'name' => $member['name'] ?? '',
                'department' => Person::DEPARTMENT_ACTING,
            ])
            ->filter(fn ($member) => $member['name'] !== '');

        $crew = collect($credits['crew'] ?? []);
        $directors = $crew
            ->where('job', 'Director')
            ->map(fn ($member) => [
                'name' => $member['name'] ?? '',
                'department' => Person::DEPARTMENT_DIRECTING,
            ])
            ->filter(fn ($member) => $member['name'] !== '');
        $producers = $crew
            ->where('job', 'Producer')
            ->take(6)
            ->map(fn ($member) => [
                'name' => $member['name'] ?? '',
                'department' => Person::DEPARTMENT_PRODUCTION,
            ])
            ->filter(fn ($member) => $member['name'] !== '');

        return response()->json([
            'duration' => (int) ($spanish['runtime'] ?? 0) ?: null,
            'release_year' => $this->tmdb->releaseYearFromDate($spanish['release_date'] ?? null),
            'image' => $posterPath ? "https://image.tmdb.org/t/p/w500{$posterPath}" : null,
            'trailer_url' => $trailerUrl,
            'rating' => round((float) ($spanish['vote_average'] ?? 0), 1) ?: null,
            'genre_ids' => collect($spanish['genres'] ?? [])->pluck('id')->values(),
            'cast' => $cast->concat($directors)->concat($producers)->values(),
            'es' => [
                'title' => $spanish['title'] ?? '',
                'sinopsis' => $spanish['overview'] ?? '',
            ],
            'en' => [
                'title' => $english['title'] ?? '',
                'sinopsis' => $english['overview'] ?? '',
                'is_available' => true,
            ],
        ]);
    }

    public function tmdbSearch()
    {
        $query = trim((string) request('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        if (!$this->tmdb->configured()) {
            return response()->json(['message' => 'TMDB no configurado en variables de entorno', 'details' => null], 500);
        }

        $url = $this->tmdb->url('/search/movie', 'es-ES').'&query='.urlencode($query);

        try {
            $response = $this->tmdb->client()->get($url);
        } catch (ConnectionException) {
            return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
        }

        if (!$response->ok()) {
            return response()->json(['message' => 'No se pudo buscar en TMDB', 'details' => null], 502);
        }

        $results = collect($response->json('results', []))
            ->take(8)
            ->map(function ($movie) {
                $releaseDate = $movie['release_date'] ?? '';
                $year = $releaseDate !== '' ? substr($releaseDate, 0, 4) : null;
                $posterPath = $movie['poster_path'] ?? null;

                return [
                    'id' => $movie['id'],
                    'title' => $movie['title'] ?? $movie['name'] ?? 'Sin titulo',
                    'year' => $year,
                    'image' => $posterPath ? "https://image.tmdb.org/t/p/w92{$posterPath}" : null,
                ];
            })
            ->values();

        return response()->json($results);
    }

    public function randomPopular()
    {
        $limit = max((int) request('limit', 3), 1);
        $language = request('language', 'es-ES');
        if (!$this->tmdb->configured()) {
            return response()->json(['message' => 'TMDB no configurado en variables de entorno', 'details' => null], 500);
        }

        $base = $this->tmdb->url('/movie/popular', $language).'&page=1';
        $genresBase = $this->tmdb->url('/genre/movie/list', $language);

        try {
            $popularResponse = $this->tmdb->client()->get($base);
        } catch (ConnectionException) {
            return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
        }
        if (!$popularResponse->ok()) {
            return response()->json(['message' => 'No se pudo obtener películas populares de TMDB', 'details' => null], 502);
        }

        $genreResponse = $this->tmdb->client()->get($genresBase);
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

    public function homePopular()
    {
        $limit = max(1, min((int) request('limit', 10), 50));
        $langCode = request('lang', 'es') === 'en' ? 'en' : 'es';

        $language = Language::query()->where('code', $langCode)->first();
        $englishLanguage = Language::query()->where('code', 'en')->first();

        $movies = Pelicula::query()
            ->with([
                'genres.translations' => function ($query) use ($language): void {
                    if ($language) {
                        $query->where('language_id', $language->id);
                    }
                },
                'people',
                'translations' => function ($query) use ($englishLanguage): void {
                    if ($englishLanguage) {
                        $query->where('language_id', $englishLanguage->id);
                    }
                },
            ])
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderByDesc('rating')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (Pelicula $movie) use ($langCode) {
                $title = $movie->title;
                $overview = $movie->sinopsis ?? '';

                if ($langCode === 'en') {
                    $english = $movie->translations->first();
                    if ($english && $english->is_available) {
                        $title = $english->title ?: $title;
                        $overview = $english->sinopsis ?? $overview;
                    }
                }

                $genres = $movie->genres
                    ->map(fn ($genre) => $genre->translations->first()?->name ?? '')
                    ->filter()
                    ->values();

                $cast = $movie->people
                    ->where('department', Person::DEPARTMENT_ACTING)
                    ->sortBy('name')
                    ->take(6)
                    ->pluck('name')
                    ->values();

                return [
                    'id' => $movie->id,
                    'title' => $title,
                    'image' => $movie->image,
                    'release_year' => $movie->release_year,
                    'genres' => $genres,
                    'vote_average' => (float) ($movie->rating ?? 0),
                    'overview' => $overview,
                    'cast' => $cast,
                ];
            });

        return response()->json($movies);
    }

    public function allTmdb()
    {
        $language = request('language', 'es-ES');
        $maxPages = max(1, min((int) request('maxPages', 20), 500));
        if (!$this->tmdb->configured()) {
            return response()->json(['message' => 'TMDB no configurado en variables de entorno', 'details' => null], 500);
        }

        $genreUrl = $this->tmdb->url('/genre/movie/list', $language);
        try {
            $genreResponse = $this->tmdb->client()->get($genreUrl);
        } catch (ConnectionException) {
            return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
        }
        $genreMap = collect($genreResponse->json('genres', []))->mapWithKeys(fn ($g) => [$g['id'] => $g['name']]);

        $movies = collect();
        $totalPages = 1;
        for ($page = 1; $page <= $maxPages && $page <= $totalPages; $page++) {
            $url = $this->tmdb->url('/movie/popular', $language).'&page='.$page;
            try {
                $response = $this->tmdb->client()->get($url);
            } catch (ConnectionException) {
                return response()->json(['message' => 'No se pudo conectar con TMDB (SSL o red).', 'details' => null], 502);
            }
            if (!$response->ok()) {
                return response()->json(['message' => 'No se pudieron obtener películas de TMDB', 'details' => null], 502);
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
