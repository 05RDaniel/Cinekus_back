# ProyectoCine Backend (Laravel)

Backend migrado desde Express a Laravel con paridad funcional de API.

## Requisitos

- PHP 8.4+
- Composer 2+
- MySQL 8+

## Instalacion

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Ajusta `.env` para MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proyectocine
DB_USERNAME=root
DB_PASSWORD=
```

## Base de datos

```bash
php artisan migrate
php artisan db:seed
```

Seeder incluido:

- Admin: `admin@proyectocine.local` / `admin123`
- User: `user@proyectocine.local` / `user123`

## Ejecutar API

```bash
php artisan serve --host=127.0.0.1 --port=3001
```

## Endpoints

- `GET /api/health`
- `POST /api/auth/login`
- `GET /api/cine/peliculas`
- `GET /api/cine/peliculas/popular/random`
- `GET /api/cine/peliculas/tmdb/all`
- `GET /api/cine/peliculas/{id}`
- `POST /api/cine/peliculas` (ADMIN)
- `PUT /api/cine/peliculas/{id}` (ADMIN)
- `DELETE /api/cine/peliculas/{id}` (ADMIN)
- `GET /api/cine/sesiones`
- `POST /api/cine/sesiones` (ADMIN)
- `GET /api/cine/salas`
- `GET /api/cine/sesiones/{id}/asientos`
- `POST /api/cine/reservas` (USER/ADMIN)
- `GET /api/cine/reservas/{usuarioId}` (auth)
