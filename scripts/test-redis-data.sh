#!/usr/bin/env bash
# =============================================================================
#  Test Redis + Data Banyak — SD Negeri Warialau
#
#  Cara pakai:
#    ./scripts/test-redis-data.sh              # interaktif (tanya jumlah)
#    ./scripts/test-redis-data.sh 2000         # 2000 berita/galeri, guru ~1000
#    ./scripts/test-redis-data.sh 5000 --iterasi=40
#    ./scripts/test-redis-data.sh --berita=3000 --guru=1500 --galeri=2000
#    ./scripts/test-redis-data.sh --skip-seed  # benchmark data yang sudah ada
# =============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

SAIL="./vendor/bin/sail"

if [[ ! -f "$SAIL" ]]; then
  echo "Error: Laravel Sail tidak ditemukan. Jalankan dari root project."
  exit 1
fi

if ! "$SAIL" exec redis redis-cli ping 2>/dev/null | grep -qi PONG; then
  echo "Error: Redis belum jalan. Jalankan: ./vendor/bin/sail up -d"
  exit 1
fi

echo "=============================================="
echo "  TEST REDIS + DATA BANYAK"
echo "  SD Negeri Warialau"
echo "=============================================="
echo

# Jika argumen pertama angka → jumlah data utama
ARGS=()
if [[ $# -gt 0 && "$1" =~ ^[0-9]+$ ]]; then
  ARGS+=("$1")
  shift
fi

ARGS+=("$@")

# Tanpa argumen: mode interaktif artisan
exec "$SAIL" artisan redis:benchmark "${ARGS[@]}"
