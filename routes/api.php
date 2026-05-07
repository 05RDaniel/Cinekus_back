<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MoviesController;
use App\Http\Controllers\Api\ReservationsController;
use App\Http\Controllers\Api\RoomsController;
use App\Http\Controllers\Api\SeatsController;
use App\Http\Controllers\Api\SessionsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'ProyectoCine API']));

Route::post('/auth/login', [AuthController::class, 'login']);

Route::prefix('cine')->group(function (): void {
    Route::get('/peliculas', [MoviesController::class, 'index']);
    Route::get('/peliculas/popular/random', [MoviesController::class, 'randomPopular']);
    Route::get('/peliculas/tmdb/all', [MoviesController::class, 'allTmdb']);
    Route::get('/peliculas/{id}', [MoviesController::class, 'show'])->whereNumber('id');
    Route::get('/sesiones', [SessionsController::class, 'index']);
    Route::get('/salas', [RoomsController::class, 'index']);
    Route::get('/sesiones/{id}/asientos', [SeatsController::class, 'bySession'])->whereNumber('id');

    Route::middleware('jwt.auth')->group(function (): void {
        Route::post('/peliculas', [MoviesController::class, 'store'])->middleware('role:ADMIN');
        Route::put('/peliculas/{id}', [MoviesController::class, 'update'])->whereNumber('id')->middleware('role:ADMIN');
        Route::delete('/peliculas/{id}', [MoviesController::class, 'destroy'])->whereNumber('id')->middleware('role:ADMIN');
        Route::post('/sesiones', [SessionsController::class, 'store'])->middleware('role:ADMIN');
        Route::post('/reservas', [ReservationsController::class, 'store'])->middleware('role:USER,ADMIN');
        Route::get('/reservas/{usuarioId}', [ReservationsController::class, 'byUser'])->whereNumber('usuarioId');
    });
});
