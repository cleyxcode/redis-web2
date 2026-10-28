#!/usr/bin/env bash
# =============================================================================
#  Cek Redis Cache + TTL — SD Negeri Warialau (sesuai penelitian Bab 4)
#
#  Alur:
#    1. Jalankan semua container Laravel Sail
#    2. Pastikan CACHE_STORE=redis + Redis sehat
#    3. Warm cache lewat endpoint API penelitian
#    4. Cek key Redis (DB cache) + sisa TTL vs strategi TTL skripsi
#
#  Cara pakai (dari folder web_laravel_sd):
#    ./scripts/cek-redis-cache.sh
#    ./scripts/cek-redis-cache.sh --no-up          # skip sail up (sudah jalan)
#    ./scripts/cek-redis-cache.sh --warm-only      # hanya warm + cek TTL
#    ./scripts/cek-redis-cache.sh --base-url=http://127.0.0.1:8000
#
#  Output log: storage/logs/cek-redis-cache-YYYYMMDD-HHMMSS.log
# =============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

SAIL="./vendor/bin/sail"
ENV_FILE=".env"
CACHE_DB="${REDIS_CACHE_DB:-1}"
BASE_URL=""
DO_UP=1
WARM_ONLY=0
TOLERANCE_SEC=90   # toleransi sisa TTL vs nilai penuh (detik)

# Warna (nonaktif jika bukan TTY)
if [[ -t 1 ]]; then
  C_OK='\033[0;32m'; C_WARN='\033[0;33m'; C_ERR='\033[0;31m'
  C_INFO='\033[0;36m'; C_BOLD='\033[1m'; C_RST='\033[0m'
else
  C_OK=''; C_WARN=''; C_ERR=''; C_INFO=''; C_BOLD=''; C_RST=''
fi

log()  { printf '%b\n' "$*" | tee -a "$LOG_FILE"; }
info() { log "${C_INFO}➜${C_RST} $*"; }
ok()   { log "${C_OK}✔${C_RST} $*"; }
warn() { log "${C_WARN}!${C_RST} $*"; }
err()  { log "${C_ERR}✖${C_RST} $*" >&2; }

usage() {
  sed -n '2,20p' "$0" | sed 's/^# \?//'
  exit 0
}

for arg in "$@"; do
  case "$arg" in
    -h|--help) usage ;;
    --no-up) DO_UP=0 ;;
    --warm-only) WARM_ONLY=1; DO_UP=0 ;;
    --base-url=*) BASE_URL="${arg#*=}" ;;
    *) err "Argumen tidak dikenal: $arg"; usage ;;
  esac
done

mkdir -p storage/logs
LOG_FILE="storage/logs/cek-redis-cache-$(date +%Y%m%d-%H%M%S).log"
: > "$LOG_FILE"

# --- Expected TTL (detik) sesuai Service Layer / Tabel 4.5 skripsi ---
# Format: logical_suffix|expected_ttl_seconds|label
EXPECTED_KEYS=(
  "berita_page_1_per10|600|Daftar berita (10 menit)"
  "guru_aktif|10800|Data guru aktif (3 jam)"
  "galeri_page_1_per12|3600|Galeri (1 jam)"
  "profil_sekolah|21600|Profil sekolah (6 jam)"
  "banner_aktif|10800|Banner aktif (3 jam)"
  "faqs_aktif|21600|FAQ aktif (6 jam)"
  "info_pendaftaran_aktif|1800|Info PPDB (30 menit)"
)

# Endpoint untuk warm cache (harus menghasilkan key di atas)
WARM_ENDPOINTS=(
  "/api/v1/berita?per_page=10"
  "/api/v1/guru"
  "/api/v1/galeri?per_page=12"
  "/api/v1/profil-sekolah"
  "/api/v1/banner"
  "/api/v1/faq"
  "/api/v1/info-pendaftaran"
)

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || { err "Perintah '$1' tidak ditemukan."; exit 1; }
}

env_get() {
  local key="$1"
  grep -E "^${key}=" "$ENV_FILE" 2>/dev/null | tail -n1 | cut -d= -f2- | tr -d '"' | tr -d "'"
}

ensure_cache_redis() {
  local current
  current="$(env_get CACHE_STORE || true)"
  if [[ "$current" != "redis" ]]; then
    warn "CACHE_STORE saat ini = '${current:-kosong}'. Mengatur ke redis..."
    if grep -qE '^CACHE_STORE=' "$ENV_FILE"; then
      sed -i.bak 's/^CACHE_STORE=.*/CACHE_STORE=redis/' "$ENV_FILE"
    else
      printf '\nCACHE_STORE=redis\n' >> "$ENV_FILE"
    fi
    ok "CACHE_STORE=redis"
  else
    ok "CACHE_STORE sudah redis"
  fi

  if ! grep -qE '^REDIS_CACHE_DB=' "$ENV_FILE"; then
    printf 'REDIS_CACHE_DB=1\n' >> "$ENV_FILE"
    ok "REDIS_CACHE_DB=1 ditambahkan"
  else
    CACHE_DB="$(env_get REDIS_CACHE_DB)"
    ok "REDIS_CACHE_DB=${CACHE_DB}"
  fi
}

sail_up() {
  info "Menjalankan semua container Sail..."
  "$SAIL" up -d
  ok "Perintah sail up -d selesai"
}

wait_redis() {
  local i
  info "Menunggu Redis siap..."
  for i in $(seq 1 40); do
    if "$SAIL" exec -T redis redis-cli ping 2>/dev/null | grep -qi PONG; then
      ok "Redis PONG (percobaan $i)"
      return 0
    fi
    sleep 2
  done
  err "Redis tidak merespons."
  exit 1
}

wait_app() {
  local i url
  url="${BASE_URL}/api/v1/health"
  info "Menunggu aplikasi siap di ${BASE_URL} ..."
  for i in $(seq 1 45); do
    if curl -fsS --connect-timeout 2 --max-time 5 "$url" >/dev/null 2>&1; then
      ok "Health OK (percobaan $i)"
      return 0
    fi
    # fallback: root
    if curl -fsS --connect-timeout 2 --max-time 5 "$BASE_URL/" >/dev/null 2>&1; then
      warn "Health endpoint belum OK, tetapi web merespons. Lanjut."
      return 0
    fi
    sleep 2
  done
  err "Aplikasi tidak merespons di ${BASE_URL}"
  exit 1
}

redis_cli() {
  "$SAIL" exec -T redis redis-cli -n "$CACHE_DB" "$@"
}

find_full_key() {
  local suffix="$1"
  local key
  # Cocokkan suffix di akhir nama key Laravel (ada prefix APP_NAME)
  while IFS= read -r key; do
    [[ -z "$key" ]] && continue
    if [[ "$key" == *"-cache-${suffix}" || "$key" == *"cache-${suffix}" || "$key" == *"${suffix}" ]]; then
      # Prefer exact logical suffix after last 'cache-'
      if [[ "$key" == *"-cache-${suffix}" ]]; then
        printf '%s\n' "$key"
        return 0
      fi
    fi
  done < <(redis_cli --scan --pattern "*${suffix}*" 2>/dev/null || true)

  # fallback: exact scan again looser
  while IFS= read -r key; do
    [[ -z "$key" ]] && continue
    if [[ "$key" == *"${suffix}" && "$key" != *:timer && "$key" != *rate* ]]; then
      printf '%s\n' "$key"
      return 0
    fi
  done < <(redis_cli --scan --pattern "*${suffix}*" 2>/dev/null || true)

  return 1
}

warm_cache() {
  local ep code
  info "Membersihkan config/cache Laravel lalu warm endpoint penelitian..."
  "$SAIL" artisan config:clear >/dev/null
  "$SAIL" artisan cache:clear >/dev/null 2>&1 || true

  for ep in "${WARM_ENDPOINTS[@]}"; do
    code="$(curl -sS -o /tmp/cek-redis-warm.out -w '%{http_code}' \
      --connect-timeout 5 --max-time 30 \
      -H 'Accept: application/json' \
      "${BASE_URL}${ep}" || echo 000)"
    if [[ "$code" =~ ^2 ]]; then
      ok "Warm ${ep} → HTTP ${code}"
    else
      warn "Warm ${ep} → HTTP ${code} (key mungkin tidak terbentuk)"
    fi
  done
}

human_duration() {
  local s="$1"
  if (( s < 0 )); then
    printf 'n/a'
    return
  fi
  local h=$((s / 3600))
  local m=$(((s % 3600) / 60))
  local r=$((s % 60))
  if (( h > 0 )); then
    printf '%dj %dm %ds' "$h" "$m" "$r"
  elif (( m > 0 )); then
    printf '%dm %ds' "$m" "$r"
  else
    printf '%ds' "$r"
  fi
}

check_ttls() {
  local entry suffix expected label full ttl status pass=0 fail=0 missing=0
  local line

  log ""
  log "${C_BOLD}════════════════════════════════════════════════════════════${C_RST}"
  log "${C_BOLD}  HASIL CEK TTL REDIS (DB ${CACHE_DB}) — sesuai penelitian${C_RST}"
  log "${C_BOLD}════════════════════════════════════════════════════════════${C_RST}"
  log ""
  printf '%-28s %-10s %-12s %-14s %s\n' "DATA" "EXPECTED" "TTL(s)" "SISA" "STATUS" | tee -a "$LOG_FILE"
  printf '%-28s %-10s %-12s %-14s %s\n' "----------------------------" "----------" "------------" "--------------" "------" | tee -a "$LOG_FILE"

  for entry in "${EXPECTED_KEYS[@]}"; do
    IFS='|' read -r suffix expected label <<< "$entry"
    full="$(find_full_key "$suffix" || true)"
    if [[ -z "${full:-}" ]]; then
      printf '%-28s %-10s %-12s %-14s %b\n' \
        "${label:0:28}" "${expected}s" "-" "-" "${C_ERR}MISSING${C_RST}" | tee -a "$LOG_FILE"
      missing=$((missing + 1))
      continue
    fi

    ttl="$(redis_cli TTL "$full" | tr -d '\r')"
    if [[ ! "$ttl" =~ ^-?[0-9]+$ ]]; then
      printf '%-28s %-10s %-12s %-14s %b\n' \
        "${label:0:28}" "${expected}s" "?" "-" "${C_ERR}ERROR${C_RST}" | tee -a "$LOG_FILE"
      fail=$((fail + 1))
      continue
    fi

    if (( ttl < 0 )); then
      # -1 no expire, -2 missing
      if (( ttl == -2 )); then
        status="MISSING"; missing=$((missing + 1))
      else
        status="NO_EXPIRE"; fail=$((fail + 1))
      fi
      printf '%-28s %-10s %-12s %-14s %b\n' \
        "${label:0:28}" "${expected}s" "$ttl" "-" "${C_ERR}${status}${C_RST}" | tee -a "$LOG_FILE"
      continue
    fi

    # TTL harus mendekati expected (belum terlalu jauh habis)
    # Lulus jika: 0 < ttl <= expected  DAN  (expected - ttl) <= TOLERANCE atau ttl >= expected*0.5
    if (( ttl > expected + 5 )); then
      status="OVER"; fail=$((fail + 1))
      printf '%-28s %-10s %-12s %-14s %b\n' \
        "${label:0:28}" "${expected}s" "$ttl" "$(human_duration "$ttl")" "${C_WARN}${status}${C_RST}" | tee -a "$LOG_FILE"
    elif (( ttl >= expected - TOLERANCE_SEC )); then
      status="OK"; pass=$((pass + 1))
      printf '%-28s %-10s %-12s %-14s %b\n' \
        "${label:0:28}" "${expected}s" "$ttl" "$(human_duration "$ttl")" "${C_OK}${status}${C_RST}" | tee -a "$LOG_FILE"
    elif (( ttl > 0 )); then
      status="LOW"; pass=$((pass + 1))  # masih valid, hanya sudah jalan lama
      printf '%-28s %-10s %-12s %-14s %b\n' \
        "${label:0:28}" "${expected}s" "$ttl" "$(human_duration "$ttl")" "${C_WARN}${status}${C_RST}" | tee -a "$LOG_FILE"
    else
      status="EXPIRED"; fail=$((fail + 1))
      printf '%-28s %-10s %-12s %-14s %b\n' \
        "${label:0:28}" "${expected}s" "$ttl" "$(human_duration "$ttl")" "${C_ERR}${status}${C_RST}" | tee -a "$LOG_FILE"
    fi

    # tampilkan full key di log saja
    echo "    key: $full" >> "$LOG_FILE"
  done

  log ""
  info "Ringkasan: OK/LOW=${pass} | GAGAL=${fail} | HILANG=${missing}"
  log ""

  info "Semua key di Redis DB ${CACHE_DB}:"
  redis_cli --scan --pattern '*' 2>/dev/null | tee -a "$LOG_FILE" || true
  log ""

  if (( missing > 0 || fail > 0 )); then
    warn "Ada key yang hilang/gagal. Pastikan API warm sukses dan CACHE_STORE=redis."
    return 1
  fi
  ok "Semua TTL cache penelitian terverifikasi."
  return 0
}

# ========================= MAIN =========================
require_cmd docker
require_cmd curl

if [[ ! -x "$SAIL" && ! -f "$SAIL" ]]; then
  err "Laravel Sail tidak ditemukan di $SAIL"
  exit 1
fi
if [[ ! -f "$ENV_FILE" ]]; then
  err "File .env tidak ditemukan."
  exit 1
fi

APP_PORT="$(env_get APP_PORT || true)"
APP_PORT="${APP_PORT:-8000}"
# APP_URL di env sering http://localhost tanpa port; pakai port Sail forward.
if [[ -z "$BASE_URL" ]]; then
  BASE_URL="http://127.0.0.1:${APP_PORT}"
fi

log "${C_BOLD}╔══════════════════════════════════════════════════════════╗${C_RST}"
log "${C_BOLD}║  CEK REDIS CACHE + TTL — SD Negeri Warialau              ║${C_RST}"
log "${C_BOLD}║  Service Layer · Redis Caching · Bab 4                   ║${C_RST}"
log "${C_BOLD}╚══════════════════════════════════════════════════════════╝${C_RST}"
log "Waktu : $(date '+%Y-%m-%d %H:%M:%S')"
log "Base  : $BASE_URL"
log "Log   : $LOG_FILE"
log ""

ensure_cache_redis
CACHE_DB="$(env_get REDIS_CACHE_DB || echo 1)"

if (( DO_UP == 1 )); then
  sail_up
fi

wait_redis

if (( WARM_ONLY == 0 || DO_UP == 1 )); then
  wait_app
fi

# Pastikan PHP membaca env terbaru
"$SAIL" artisan config:clear >/dev/null 2>&1 || true

warm_cache
sleep 1

set +e
check_ttls
rc=$?
set -e

log ""
ok "Selesai. Detail tersimpan di: $LOG_FILE"
exit "$rc"
