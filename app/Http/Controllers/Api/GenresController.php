<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class GenresController extends Controller
{
    public function index(): JsonResponse
    {
        $lang = request('lang', 'es');
        $language = Language::query()->where('code', $lang)->first();
        if (!$language) {
            return response()->json(['message' => 'Idioma no soportado', 'details' => null], 400);
        }

        $genres = Genre::query()
            ->with(['translations' => fn ($query) => $query->where('language_id', $language->id)])
            ->orderBy('id')
            ->get()
            ->map(fn (Genre $genre) => [
                'id' => $genre->id,
                'name' => $genre->translations->first()?->name ?? (string) $genre->id,
            ])
            ->values();

        return response()->json($genres);
    }
}
