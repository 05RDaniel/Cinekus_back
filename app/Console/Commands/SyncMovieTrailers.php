<?php

namespace App\Console\Commands;

use App\Models\Pelicula;
use App\Support\Tmdb\TmdbClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class SyncMovieTrailers extends Command
{
    protected $signature = 'movies:sync-trailers {--force : Actualizar aunque ya tengan tráiler}';

    protected $description = 'Rellena trailer_url de películas existentes consultando TMDB';

    public function handle(TmdbClient $tmdb): int
    {
        if (!$tmdb->configured()) {
            $this->error('TMDB no configurado. Define TMDB_API_KEY o TMDB_READ_ACCESS_TOKEN en .env');

            return self::FAILURE;
        }

        $query = Pelicula::query()->orderBy('id');
        if (!$this->option('force')) {
            $query->where(function ($builder): void {
                $builder->whereNull('trailer_url')->orWhere('trailer_url', '');
            });
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

            if (!$match || empty($match['id'])) {
                $this->warn('  Sin coincidencia en TMDB');
                $skipped++;

                continue;
            }

            $trailerUrl = $tmdb->fetchTrailerUrl((int) $match['id']);
            if (!$trailerUrl) {
                $this->warn('  Sin tráiler en YouTube en TMDB');
                $skipped++;

                continue;
            }

            $movie->trailer_url = $trailerUrl;
            $movie->save();

            $this->info("  Tráiler guardado: {$trailerUrl}");
            $updated++;

            usleep(300_000);
        }

        $this->newLine();
        $this->info("Listo. Actualizadas: {$updated}. Omitidas: {$skipped}. Fallidas: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
