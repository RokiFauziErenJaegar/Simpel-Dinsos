#!/usr/bin/env bash
# Setup MinIO untuk SIMPEL DINSOS — siap pakai dalam 30 detik
#
# Prereq: Docker + Docker Compose terpasang
# Usage: bash infra/setup-minio.sh

set -e

cd "$(dirname "$0")"

echo "🚀 Setup MinIO untuk SIMPEL DINSOS"
echo ""

# Generate password kuat jika belum ada
if [ ! -f .env ]; then
    echo "Generate kredensial baru…"
    cat > .env <<EOF
MINIO_ROOT_USER=simpel-admin
MINIO_ROOT_PASSWORD=$(openssl rand -base64 24)
EOF
    echo "✓ .env dibuat: infra/.env"
fi

# Jalankan stack
echo ""
echo "▶ Menjalankan MinIO + auto-create bucket…"
docker compose -f docker-compose.minio.yml --env-file .env up -d

echo ""
echo "⏳ Menunggu MinIO ready…"
sleep 8

# Cek health
if curl -fs http://localhost:9000/minio/health/live > /dev/null 2>&1; then
    echo "✓ MinIO ready di http://localhost:9000"
else
    echo "⚠ Health check gagal, cek dengan: docker compose -f docker-compose.minio.yml logs minio"
    exit 1
fi

echo ""
echo "=========================================="
echo "  MinIO siap. Update .env aplikasi:"
echo "=========================================="
echo "  SECURE_DISK_DRIVER=minio"
echo "  MINIO_BUCKET=simpel-dinsos"
echo "  MINIO_ENDPOINT=http://127.0.0.1:9000"
echo "  MINIO_KEY=$(grep MINIO_ROOT_USER .env | cut -d= -f2)"
echo "  MINIO_SECRET=$(grep MINIO_ROOT_PASSWORD .env | cut -d= -f2)"
echo ""
echo "Buat access key terpisah (RECOMMENDED untuk produksi):"
echo "  1. Buka http://localhost:9001"
echo "  2. Login dengan kredensial di atas"
echo "  3. Menu 'Access Keys' → Create access key"
echo "  4. Pakai access key tersebut untuk MINIO_KEY & MINIO_SECRET"
echo ""
echo "Migrasi berkas lama (jika ada):"
echo "  cd .. && php artisan storage:migrate-sensitive"
echo ""
