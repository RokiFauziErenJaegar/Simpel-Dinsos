#!/bin/sh
# SIMPEL DINSOS — Container entrypoint untuk Railway
# Pakai /bin/sh (Alpine BusyBox) — tidak pakai `set -e` agar tidak exit di error minor.

ROLE="${CONTAINER_ROLE:-web}"

echo "[entrypoint] ============================================="
echo "[entrypoint] SIMPEL DINSOS container starting..."
echo "[entrypoint] CONTAINER_ROLE=$ROLE"
echo "[entrypoint] PORT=${PORT:-8080}"
echo "[entrypoint] DB_HOST=${DB_HOST:-(not set)}"
echo "[entrypoint] DB_DATABASE=${DB_DATABASE:-(not set)}"
echo "[entrypoint] APP_ENV=${APP_ENV:-production}"
echo "[entrypoint] ============================================="

# 1. Substitute $PORT di nginx.conf
PORT="${PORT:-8080}"
sed -i "s/listen 8080 default_server;/listen $PORT default_server;/g" /etc/nginx/nginx.conf
sed -i "s/listen \[::\]:8080 default_server;/listen [::]:$PORT default_server;/g" /etc/nginx/nginx.conf
echo "[entrypoint] nginx will listen on $PORT"

# 2. Setup storage (Volume Railway mounted di /app/storage)
mkdir -p /app/storage/app/public \
         /app/storage/app/secure \
         /app/storage/app/private/outbox \
         /app/storage/logs \
         /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/framework/testing 2>/dev/null
chown -R app:app /app/storage /app/bootstrap/cache 2>/dev/null
chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null

# Storage symlink
[ -e /app/public/storage ] || ln -sf /app/storage/app/public /app/public/storage
echo "[entrypoint] Storage ready"

# 3. APP_KEY check
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] FATAL: APP_KEY tidak di-set"
    exit 1
fi

cd /app

# 4. Migrasi & seed — HANYA jalan di service web (queue/scheduler skip)
if [ "$ROLE" = "web" ] && [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force --no-interaction || echo "[entrypoint] migrate warning"

    USER_COUNT=$(php -r "
        try {
            \$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}', [PDO::ATTR_TIMEOUT => 5]);
            echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        } catch (Exception \$e) { echo 0; }
    " 2>/dev/null)
    USER_COUNT="${USER_COUNT:-0}"
    echo "[entrypoint] Existing users: $USER_COUNT"

    if [ "$USER_COUNT" = "0" ]; then
        echo "[entrypoint] Seeding..."
        php artisan db:seed --force --no-interaction || echo "[entrypoint] seed warning"
    fi
fi

# 5. Cache Laravel & Filament (semua role butuh ini — config/route untuk akses Eloquent)
echo "[entrypoint] Caching..."
php artisan config:cache --no-interaction 2>&1
php artisan route:cache --no-interaction 2>&1
if [ "$ROLE" = "web" ]; then
    php artisan view:cache --no-interaction 2>&1
    php artisan event:cache --no-interaction 2>&1
    php artisan filament:cache-components --no-interaction 2>&1
    php artisan icons:cache --no-interaction 2>&1
fi
echo "[entrypoint] Cache done"

# 6. Branching berdasarkan role
case "$ROLE" in
    queue)
        # Worker dijalankan dalam loop. Tiap exit (max-time/max-jobs/error),
        # Railway harusnya restart container. Tapi `Railway: Completed` status
        # menunjukkan exit kadang dianggap deploy selesai, bukan crash.
        # Loop di shell ini memastikan worker selalu jalan selama container alive.
        echo "[entrypoint] Starting queue worker loop..."
        while true; do
            php artisan queue:work --tries=3 --max-time=21600 --sleep=3 --backoff=10 --max-jobs=1000 || true
            echo "[entrypoint] Worker exited, restarting in 2s..."
            sleep 2
        done
        ;;
    scheduler)
        echo "[entrypoint] Starting scheduler..."
        exec php artisan schedule:work
        ;;
    web|*)
        echo "[entrypoint] Starting php-fpm in background..."
        php-fpm --daemonize

        echo "[entrypoint] Waiting for php-fpm socket..."
        for i in 1 2 3 4 5 6 7 8 9 10; do
            [ -S /var/run/php/php-fpm.sock ] && echo "[entrypoint] php-fpm ready" && break
            sleep 0.5
        done

        echo "[entrypoint] Starting nginx (foreground)..."
        exec nginx -g "daemon off;"
        ;;
esac
