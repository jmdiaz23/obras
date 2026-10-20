#!/bin/sh
set -e

cd /var/www/html

# ---------------------------------------------------------------------------
# 1. Archivo .env
# ---------------------------------------------------------------------------
# Si no existe, partimos de la plantilla pensada para Docker.
if [ ! -f .env ]; then
    echo "==> Creando .env a partir de .env.docker.example"
    cp .env.docker.example .env
fi

# ---------------------------------------------------------------------------
# 2. APP_KEY
# ---------------------------------------------------------------------------
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "==> Generando APP_KEY"
    php artisan key:generate --force --no-interaction
fi

# ---------------------------------------------------------------------------
# 3. Permisos
# ---------------------------------------------------------------------------
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# ---------------------------------------------------------------------------
# 4. Esperar a la base de datos
# ---------------------------------------------------------------------------
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    echo "==> Esperando a MySQL en ${DB_HOST:-db}:${DB_PORT:-3306}..."
    until php -r '
        try {
            new PDO(
                "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD"),
                [PDO::ATTR_TIMEOUT => 3]
            );
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    '; do
        sleep 2
    done
    echo "==> MySQL disponible."
fi

# ---------------------------------------------------------------------------
# 5. Migraciones y datos de demostración
# ---------------------------------------------------------------------------
echo "==> Ejecutando migraciones"
php artisan migrate --force --no-interaction

if [ "${SEED_DEMO:-false}" = "true" ]; then
    echo "==> Cargando datos de demostración"
    php artisan db:seed --class=DemoObraSeeder --force --no-interaction || true
fi

# ---------------------------------------------------------------------------
# 6. Cachés de Laravel
# ---------------------------------------------------------------------------
php artisan optimize:clear >/dev/null 2>&1 || true
php artisan view:cache >/dev/null 2>&1 || true

# ---------------------------------------------------------------------------
# 7. Arrancar el proceso principal (php-fpm por defecto)
# ---------------------------------------------------------------------------
exec "$@"
