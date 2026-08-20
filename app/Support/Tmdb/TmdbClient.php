<?php

namespace App\Support\Tmdb;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Centralizes all TMDB access shared by MoviesController and the
 * movies:sync-* console commands (client building, URL composition and
 * the helpers used to match movies and extract release year / trailers).
 */
class TmdbClient
{
    public function configured(): bool
    {
        return (bool) env('TMDB_READ_ACCESS_TOKEN') || (bool) env('TMDB_API_KEY');
    }

    public function client(): PendingRequest
    {
        $disableSslVerify = filter_var(env('TMDB_DISABLE_SSL_VERIFY', false), FILTER_VALIDATE_BOOL);
        $client = Http::withHeaders($this->headers());
        if ($disableSslVerify) {
            $client = $client->withOptions(['verify' => false]);
        }

        return $client;
    }

    public function url(string $path, string $language): string
    {
        $readToken = env('TMDB_READ_ACCESS_TOKEN');
        $apiKey = env('TMDB_API_KEY');
        $url = "https://api.themoviedb.org/3{$path}?language=".urlencode($language);
        if (!$readToken && $apiKey) {
            $url .= '&api_key='.urlencode($apiKey);
        }

        return $url;
    }

    public function releaseYearFromDate(?string $releaseDate): ?int
    {
        if ($releaseDate === null || $releaseDate === '' || strlen($releaseDate) < 4) {
            return null;
        }

        $year = (int) substr($releaseDate, 0, 4);

        return $year > 0 ? $year : null;
    }

    /** @param array<string, mixed>|null $videos */
    public function trailerUrlFromVideos(?array $videos): ?string
    {
        if ($videos === null) {
            return null;
        }

        $candidates = collect($videos['results'] ?? [])
            ->filter(fn (array $video) => ($video['site'] ?? '') === 'YouTube' && !empty($video['key']));

        $trailer = $candidates->first(fn (array $video) => ($video['type'] ?? '') === 'Trailer');
        if (!$trailer) {
            $trailer = $candidates->first(fn (array $video) => in_array($video['type'] ?? '', ['Teaser', 'Clip'], true));
        }

        if (!$trailer) {
            return null;
        }

        return 'https://www.youtube.com/watch?v='.$trailer['key'];
    }

    public function fetchTrailerUrl(int $tmdbId): ?string
    {
        $response = $this->client()->get($this->url("/movie/{$tmdbId}/videos", 'es-ES'));
        if (!$response->ok()) {
            return null;
        }

        return $this->trailerUrlFromVideos($response->json());
    }

    /** @return array<string, mixed>|null */
    public function findMatch(string $title, ?string $image): ?array
    {
        $response = $this->client()->get($this->url('/search/movie', 'es-ES').'&query='.urlencode($title));
        if (!$response->ok()) {
            return null;
        }

        $results = collect($response->json('results', []));
        if ($results->isEmpty()) {
            return null;
        }

        $expectedPoster = $this->posterPathFromImage($image);
        if ($expectedPoster) {
            $byPoster = $results->first(
                fn (array $result) => ($result['poster_path'] ?? null) === $expectedPoster
            );
            if ($byPoster) {
                return $byPoster;
            }
        }

        $normalizedTitle = $this->normalizeTitle($title);
        $byTitle = $results->first(function (array $result) use ($normalizedTitle) {
            $candidate = $this->normalizeTitle($result['title'] ?? $result['name'] ?? '');

            return $candidate !== '' && $candidate === $normalizedTitle;
        });
        if ($byTitle) {
            return $byTitle;
        }

        return $results->first();
    }

    private function headers(): array
    {
        $headers = ['accept' => 'application/json'];
        $readToken = env('TMDB_READ_ACCESS_TOKEN');
        if ($readToken) {
            $headers['Authorization'] = "Bearer {$readToken}";
        }

        return $headers;
    }

    private function posterPathFromImage(?string $image): ?string
    {
        if (!$image || !str_contains($image, 'image.tmdb.org')) {
            return null;
        }

        if (!preg_match('#/t/p/[^/]+/([^/?]+)#', $image, $matches)) {
            return null;
        }

        return '/'.$matches[1];
    }

    private function normalizeTitle(string $title): string
    {
        return mb_strtolower(trim($title));
    }
}
