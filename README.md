# ProyectoCine Backend (Laravel)

API REST con JWT. Puerto de desarrollo **8000**.

## Requisitos

- PHP 8.3+
- Composer 2+
- MySQL 8+

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

Usuarios semilla: `admin@proyectocine.local` / `admin123` y `user@proyectocine.local` / `user123`.

Las rutas de admin y de reserva exigen JWT y rol; no hay variables de entorno para desactivar esos controles.

Base URL: `http://127.0.0.1:8000/api`. Detalle de rutas en el README de la raíz del repo.
