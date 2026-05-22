#!/bin/bash
set -e

# SIMPEL DINSOS — Container entrypoint untuk Railway
# Dijalankan tiap container start: setup storage, migrate, cache, lalu start supervisord.

echo "[entrypoint] Starting SIMPEL DINSOS container..."

# ============================================================
# 1. Setup PORT — Railway inject $PORT (random), default 8080
# ============================================================
PORT="${PORT:-8080}"
echo "[entrypoint] Listening on port $PORT"
sed -i "s/listen 8080 default_server;/listen $PORT default_server;/" /etc/nginx/nginx.conf
sed -i "s/listen \[::\]:8080 default_server;/listen [::]:$PORT default_server;/" /etc/nginx/nginx.conf

# ============================================================
# 2. Setup storage di Volume
# Railway Volume di-mount ke /app/storage (sesuai config railway.json).
# Pastikan struktur folder & symlink ada.
# ============================================================
echo "[entrypoint] Setup storage directories..."
mkdir -p /app/storage/app/public \
         /app/storage/app/secure \
         /app/storage/app/private \
         /app/storage/app/private/outbox \
         /app/storage/logs \
         /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/framework/testing

# Pastikan permission benar (Volume kadang reset owner)
chown -R app:app /app/storage /app/bootstrap/cache 2>/dev/null || true
chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null || true

# Storage symlink: public/storage → storage/app/public
if [ ! -L /app/public/storage ]; then
    echo "[entrypoint] Creating storage symlink..."
    rm -rf /app/public/storage
    ln -s /app/storage/app/public /app/public/storage
fi

# ============================================================
# 3. Tunggu database MySQL siap
# ============================================================
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "[entrypoint] Waiting for database $DB_HOST:$DB_PORT..."
    timeout=30
    until php -r "
        try {
            new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}', [PDO::ATTR_TIMEOUT => 2]);
            exit(0);
        } catch (Exception \$e) { exit(1); }
    " 2>/dev/null; do
        timeout=$((timeout - 1))
        if [ $timeout -le 0 ]; then
            echo "[entrypoint] WARNING: Database tidak siap setelah 30 detik. Lanjut saja, tapi migrasi mungkin gagal."
            break
        fi
        sleep 1
    done
fi

cd /app

# ============================================================
# 4. APP_KEY check — wajib di-set di Railway env vars
# ============================================================
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] ERROR: APP_KEY belum di-set. Generate lokal dengan:"
    echo "  php artisan key:generate --show"
    echo "  lalu copy ke Railway env vars dengan format 'base64:xxxxx'"
    exit 1
fi

# ============================================================
# 5. Migrasi database (idempoten, aman dijalankan tiap deploy)
# ============================================================
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction || {
    echo "[entrypoint] WARNING: Migration failed, container tetap start (mungkin tabel sudah ada)"
}

# ============================================================
# 6. Seeder — hanya jalan jika tabel users masih kosong
# ============================================================
USERS_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$USERS_COUNT" = "0" ]; then
    echo "[entrypoint] DB kosong, jalankan seeder..."
    php artisan db:seed --force --no-interaction || echo "[entrypoint] WARNING: Seeder gagal"
else
    echo "[entrypoint] DB sudah ada $USERS_COUNT user, skip seeder."
fi

# ============================================================
# 7. Cache Laravel & Filament untuk performa maksimal
# ============================================================
echo "[entrypoint] Caching config, route, view, events, filament..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction
php artisan filament:cache-components --no-interaction || true
php artisan icons:cache --no-interaction || true

# ============================================================
# 8. Cache application (clear stale data dari deploy sebelumnya)
# ============================================================
php artisan cache:clear --no-interaction || true

echo "[entrypoint] Bootstrap selesai. Starting supervisord..."
echo ""

# ============================================================
# 9. Eksekusi CMD (supervisord)
# ============================================================
exec "$@"
