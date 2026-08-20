<?php

namespace App\Console\Commands;

use App\Models\Pelicula;
use App\Support\Tmdb\TmdbClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class SyncMovieReleaseYears extends Command
{
    protected $signature = 'movies:sync-release-years {--force : Actualizar aunque ya tengan año}';

    protected $description = 'Rellena release_year de películas existentes consultando TMDB';

    public function handle(TmdbClient $tmdb): int
    {
        if (!$tmdb->configured()) {
            $this->error('TMDB no configurado. Define TMDB_API_KEY o TMDB_READ_ACCESS_TOKEN en .env');

            return self::FAILURE;
        }

        $query = Pelicula::query()->orderBy('id');
        if (!$this->option('force')) {
            $query->whereNull('release_year');
        }

        $movies = $query->get();
        if ($movies->isEmpty()) {
            $this->info('No hay películas pendientes de sincronizar.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($movies as $movie) {
            $this->line("Procesando #{$movie->id}: {$movie->title}");

            try {
                $match = $tmdb->findMatch($movie->title, $movie->image);
            } catch (ConnectionException $exception) {
                $this->error("  Error de conexión con TMDB: {$exception->getMessage()}");
                $failed++;

                continue;
            }

            if (!$match) {
                $this->warn('  Sin coincidencia en TMDB');
                $skipped++;

                continue;
            }

            $year = $tmdb->releaseYearFromDate($match['release_date'] ?? null);
            if (!$year) {
                $this->warn('  Coincidencia sin fecha de estreno en TMDB');
                $skipped++;

                continue;
            }

            $movie->release_year = $year;
            $movie->save();

            $tmdbTitle = $match['title'] ?? $match['name'] ?? '—';
            $this->info("  Actualizado a {$year} (TMDB: {$tmdbTitle})");
            $updated++;

            usleep(300_000);
        }

        $this->newLine();
        $this->info("Listo. Actualizadas: {$updated}. Omitidas: {$skipped}. Fallidas: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
