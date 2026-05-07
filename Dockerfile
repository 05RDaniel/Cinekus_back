FROM php:8.4-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    zip

# Instalar extensiones de base de datos
RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar archivos
COPY . .

# Instalar dependencias Laravel
RUN composer install --no-dev --optimize-autoloader

# Puerto Render
EXPOSE 10000

# Comando inicio
CMD php artisan serve --host=0.0.0.0 --port=10000
