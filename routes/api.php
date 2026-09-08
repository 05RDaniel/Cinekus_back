<?php



use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\GenresController;

use App\Http\Controllers\Api\LanguagesController;

use App\Http\Controllers\Api\MoviesController;

use App\Http\Controllers\Api\ReservationsController;

use App\Http\Controllers\Api\RoomsController;

use App\Http\Controllers\Api\SeatTypesController;

use App\Http\Controllers\Api\SeatsController;

use App\Http\Controllers\Api\SessionsController;

use App\Http\Controllers\Api\UsersController;

use Illuminate\Support\Facades\Route;



Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'ProyectoCine API']));



Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);



Route::prefix('cine')->group(function (): void {

    Route::get('/peliculas', [MoviesController::class, 'index']);

    Route::get('/peliculas/popular/home', [MoviesController::class, 'homePopular']);

    Route::get('/peliculas/popular/random', [MoviesController::class, 'randomPopular']);

    Route::get('/peliculas/tmdb/all', [MoviesController::class, 'allTmdb']);

    Route::get('/peliculas/tmdb/search', [MoviesController::class, 'tmdbSearch']);

    Route::get('/peliculas/tmdb/{tmdbId}', [MoviesController::class, 'tmdbImport'])->whereNumber('tmdbId');

    Route::get('/generos', [GenresController::class, 'index']);

    Route::get('/idiomas', [LanguagesController::class, 'index']);

    Route::get('/peliculas/{id}', [MoviesController::class, 'show'])->whereNumber('id');

    Route::get('/sesiones', [SessionsController::class, 'index']);

    Route::get('/salas', [RoomsController::class, 'index']);

    Route::get('/tipos-asiento', [SeatTypesController::class, 'index']);

    Route::get('/sesiones/{id}/asientos', [SeatsController::class, 'bySession'])->whereNumber('id');



    Route::middleware('api.jwt')->group(function (): void {

        Route::post('/peliculas', [MoviesController::class, 'store'])->middleware('role:ADMIN');

        Route::put('/peliculas/{id}', [MoviesController::class, 'update'])->whereNumber('id')->middleware('role:ADMIN');

        Route::delete('/peliculas/{id}', [MoviesController::class, 'destroy'])->whereNumber('id')->middleware('role:ADMIN');

        Route::post('/sesiones', [SessionsController::class, 'store'])->middleware('role:ADMIN');

        Route::get('/sesiones/{id}', [SessionsController::class, 'show'])->whereNumber('id')->middleware('role:ADMIN');

        Route::put('/sesiones/{id}', [SessionsController::class, 'update'])->whereNumber('id')->middleware('role:ADMIN');

        Route::delete('/sesiones/{id}', [SessionsController::class, 'destroy'])->whereNumber('id')->middleware('role:ADMIN');

        Route::post('/salas', [RoomsController::class, 'store'])->middleware('role:ADMIN');

        Route::get('/salas/{id}', [RoomsController::class, 'show'])->whereNumber('id')->middleware('role:ADMIN');

        Route::put('/salas/{id}', [RoomsController::class, 'update'])->whereNumber('id')->middleware('role:ADMIN');

        Route::delete('/salas/{id}', [RoomsController::class, 'destroy'])->whereNumber('id')->middleware('role:ADMIN');

        Route::get('/usuarios', [UsersController::class, 'index'])->middleware('role:ADMIN');

        Route::get('/usuarios/{id}', [UsersController::class, 'show'])->whereNumber('id')->middleware('role:ADMIN');

        Route::post('/usuarios', [UsersController::class, 'store'])->middleware('role:ADMIN');

        Route::put('/usuarios/{id}', [UsersController::class, 'update'])->whereNumber('id')->middleware('role:ADMIN');

        Route::delete('/usuarios/{id}', [UsersController::class, 'destroy'])->whereNumber('id')->middleware('role:ADMIN');

        Route::get('/reservas', [ReservationsController::class, 'index'])->middleware('role:ADMIN');

        Route::post('/reservas', [ReservationsController::class, 'store'])->middleware(['api.jwt:required', 'role:USER,ADMIN']);

        Route::put('/reservas/{id}', [ReservationsController::class, 'update'])->whereNumber('id')->middleware('role:ADMIN');

        Route::delete('/reservas/{id}', [ReservationsController::class, 'destroy'])->whereNumber('id')->middleware('role:ADMIN');

        Route::get('/reservas/{userId}', [ReservationsController::class, 'byUser'])->whereNumber('userId');

    });

});

