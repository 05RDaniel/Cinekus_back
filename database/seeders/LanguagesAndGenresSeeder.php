<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguagesAndGenresSeeder extends Seeder
{
  /** @var array<int, array{es: string, en: string}> */
  private const TMDB_GENRES = [
    28 => ['es' => 'Acción', 'en' => 'Action'],
    12 => ['es' => 'Aventura', 'en' => 'Adventure'],
    16 => ['es' => 'Animación', 'en' => 'Animation'],
    35 => ['es' => 'Comedia', 'en' => 'Comedy'],
    80 => ['es' => 'Crimen', 'en' => 'Crime'],
    99 => ['es' => 'Documental', 'en' => 'Documentary'],
    18 => ['es' => 'Drama', 'en' => 'Drama'],
    10751 => ['es' => 'Familia', 'en' => 'Family'],
    14 => ['es' => 'Fantasía', 'en' => 'Fantasy'],
    36 => ['es' => 'Historia', 'en' => 'History'],
    27 => ['es' => 'Terror', 'en' => 'Horror'],
    10402 => ['es' => 'Música', 'en' => 'Music'],
    9648 => ['es' => 'Misterio', 'en' => 'Mystery'],
    10749 => ['es' => 'Romance', 'en' => 'Romance'],
    878 => ['es' => 'Ciencia ficción', 'en' => 'Science Fiction'],
    10770 => ['es' => 'Película de TV', 'en' => 'TV Movie'],
    53 => ['es' => 'Suspense', 'en' => 'Thriller'],
    10752 => ['es' => 'Bélica', 'en' => 'War'],
    37 => ['es' => 'Western', 'en' => 'Western'],
  ];

  public function run(): void
  {
    DB::table('languages')->updateOrInsert(['code' => 'es'], ['name' => 'Español']);
    DB::table('languages')->updateOrInsert(['code' => 'en'], ['name' => 'English']);

    $spanishId = DB::table('languages')->where('code', 'es')->value('id');
    $englishId = DB::table('languages')->where('code', 'en')->value('id');

    foreach (self::TMDB_GENRES as $genreId => $names) {
      DB::table('genres')->updateOrInsert(['id' => $genreId], ['parent_id' => null]);

      DB::table('genre_translations')->updateOrInsert(
        ['genre_id' => $genreId, 'language_id' => $spanishId],
        ['name' => $names['es']]
      );
      DB::table('genre_translations')->updateOrInsert(
        ['genre_id' => $genreId, 'language_id' => $englishId],
        ['name' => $names['en']]
      );
    }
  }
}
