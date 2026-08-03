#!/bin/bash

# ═══════════════════════════════════════════════════════════════════════════════
#  INSTALL SCRIPT — Sistem Informasi SD Negeri Warialau
#  Mendukung: Linux & macOS
#  Kebutuhan: Docker Desktop / Docker Engine + Docker Compose
#  Menjalankan aplikasi via Laravel Sail
# ═══════════════════════════════════════════════════════════════════════════════

set -e

# ── Warna output ──────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

SAIL="./vendor/bin/sail"

print_header() {
    echo ""
    echo -e "${BLUE}${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}${BOLD}║     Sistem Informasi SD Negeri Warialau              ║${NC}"
    echo -e "${BLUE}${BOLD}║     Laravel Sail Installer — Linux / macOS           ║${NC}"
    echo -e "${BLUE}${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
    echo ""
}

ok()   { echo -e "${GREEN}  ✔  $1${NC}"; }
info() { echo -e "${CYAN}  ➜  $1${NC}"; }
warn() { echo -e "${YELLOW}  ⚠  $1${NC}"; }
fail() { echo -e "${RED}  ✘  $1${NC}"; exit 1; }
step() { echo -e "\n${BOLD}${BLUE}▶ $1${NC}"; }

# ── Cek Docker ────────────────────────────────────────────────────────────────
check_docker() {
    step "Memeriksa instalasi Docker"
    if ! command -v docker &>/dev/null; then
        fail "Docker tidak ditemukan. Install Docker dari https://docs.docker.com/get-docker/"
    fi
    ok "Docker ditemukan: $(docker --version)"

    if ! docker compose version &>/dev/null && ! command -v docker-compose &>/dev/null; then
        fail "Docker Compose tidak ditemukan. Install Docker Compose terlebih dahulu."
    fi
    ok "Docker Compose ditemukan"

    if ! docker info &>/dev/null; then
        fail "Docker daemon tidak berjalan. Jalankan Docker Desktop terlebih dahulu."
    fi
    ok "Docker daemon aktif"
}

# ── Setup .env ────────────────────────────────────────────────────────────────
setup_env() {
    step "Menyiapkan file konfigurasi .env"

    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            ok ".env dibuat dari .env.example"
        else
            fail "File .env.example tidak ditemukan"
        fi
    else
        ok ".env sudah ada, melewati langkah ini"
    fi

    # Pastikan konfigurasi host untuk Sail
    sed -i 's/^DB_HOST=.*/DB_HOST=mysql/' .env
    sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' .env
    sed -i 's/^MAIL_HOST=.*/MAIL_HOST=mailpit/' .env
    sed -i 's/^DB_USERNAME=.*/DB_USERNAME=sail/' .env
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=password/' .env

    if ! grep -q '^WWWUSER=' .env; then
        echo "WWWUSER=$(id -u)" >> .env
        echo "WWWGROUP=$(id -g)" >> .env
    else
        sed -i "s/^WWWUSER=.*/WWWUSER=$(id -u)/" .env
        sed -i "s/^WWWGROUP=.*/WWWGROUP=$(id -g)/" .env
    fi

    if ! grep -q '^APP_PORT=' .env; then
        echo "APP_PORT=8000" >> .env
    fi

    ok "Konfigurasi .env disesuaikan untuk Laravel Sail"
}

# ── Composer install (host) jika vendor belum ada ─────────────────────────────
ensure_vendor() {
    step "Memastikan dependensi Composer tersedia"
    if [ ! -f "./vendor/bin/sail" ]; then
        if ! command -v composer &>/dev/null; then
            fail "Composer tidak ditemukan dan vendor/ belum ada. Install Composer atau jalankan: docker run --rm -v \"\$(pwd):/app\" -w /app composer:2 install"
        fi
        composer install
        ok "composer install selesai"
    else
        ok "Laravel Sail sudah tersedia di vendor/"
    fi
}

# ── Build & jalankan Sail ─────────────────────────────────────────────────────
start_sail() {
    step "Build & menjalankan Laravel Sail (pertama kali bisa lama)"
    $SAIL up -d --build
    ok "Container Sail berjalan"
    echo ""
    $SAIL ps
}

# ── Setup Laravel ─────────────────────────────────────────────────────────────
setup_laravel() {
    step "Setup Laravel di dalam Sail"

    info "Menunggu MySQL siap..."
    sleep 8

    if ! grep -q '^APP_KEY=base64:' .env; then
        info "Generate APP_KEY..."
        $SAIL artisan key:generate --force
        ok "APP_KEY berhasil digenerate"
    else
        ok "APP_KEY sudah ada"
    fi

    info "Menjalankan database migration..."
    $SAIL artisan migrate --force
    ok "Migration selesai"

    info "Menjalankan seeder..."
    $SAIL artisan db:seed --force
    ok "Seeder selesai"

    info "Membuat storage symlink..."
    $SAIL artisan storage:link || true
    ok "Storage symlink dibuat"
}

# ── Selesai ───────────────────────────────────────────────────────────────────
print_done() {
    echo ""
    echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}${BOLD}║            INSTALASI BERHASIL! ✔                     ║${NC}"
    echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  🌐  Website   : ${BOLD}http://localhost:8000${NC}"
    echo -e "  🔐  Admin     : ${BOLD}http://localhost:8000/admin${NC}"
    echo -e "  📧  Mailpit   : ${BOLD}http://localhost:8025${NC}"
    echo -e "  👤  Email     : ${BOLD}admin@admin.com${NC}"
    echo -e "  🔑  Password  : ${BOLD}admin${NC}"
    echo ""
    echo -e "${YELLOW}  ⚠  Ganti password admin setelah login pertama!${NC}"
    echo ""
    echo -e "  Perintah berguna (Laravel Sail):"
    echo -e "    ./vendor/bin/sail up -d       — jalankan container"
    echo -e "    ./vendor/bin/sail stop        — hentikan container"
    echo -e "    ./vendor/bin/sail artisan ... — perintah Artisan"
    echo -e "    ./vendor/bin/sail shell       — masuk ke container"
    echo -e "    ./vendor/bin/sail logs -f     — lihat log"
    echo -e "    ./vendor/bin/sail down -v     — hapus container & volume"
    echo ""
}

# ── Main ──────────────────────────────────────────────────────────────────────
print_header
check_docker
setup_env
ensure_vendor
start_sail
setup_laravel
print_done
