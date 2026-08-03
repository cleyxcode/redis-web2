#!/usr/bin/env bash
# =============================================================================
#  Benchmark Redis Cache — SD Negeri Warialau
#  Cara jalankan (dari root project):
#    ./scripts/benchmark-redis.sh
#
#  Script ini membandingkan:
#    1) Endpoint API: Cache MISS (query DB) vs Cache HIT (Redis)
#    2) Service layer: Tanpa cache vs Cache Database (MySQL) vs Cache Redis
# =============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-http://localhost:8000}"
ITERATIONS="${ITERATIONS:-40}"
HTTP_ROUNDS="${HTTP_ROUNDS:-8}"
SAIL="./vendor/bin/sail"

# Warna (aman jika terminal tidak support)
if [[ -t 1 ]]; then
  BOLD='\033[1m'
  GREEN='\033[0;32m'
  YELLOW='\033[0;33m'
  CYAN='\033[0;36m'
  RED='\033[0;31m'
  DIM='\033[2m'
  NC='\033[0m'
else
  BOLD=''; GREEN=''; YELLOW=''; CYAN=''; RED=''; DIM=''; NC=''
fi

hr() { printf '%s\n' "--------------------------------------------------------------------------------"; }
title() {
  echo
  hr
  printf "${BOLD}%s${NC}\n" "$1"
  hr
}

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo -e "${RED}Perintah '$1' tidak ditemukan.${NC}"
    exit 1
  }
}

ms_from_sec() {
  # detik (float) -> ms (1 desimal)
  awk -v s="$1" 'BEGIN { printf "%.1f", s * 1000 }'
}

avg_list() {
  # rata-rata angka di stdin (satu per baris)
  awk '{ s+=$1; n++ } END { if (n>0) printf "%.1f", s/n; else print "0.0" }'
}

curl_ms() {
  local url="$1"
  local out code t
  out="$(curl -sS -o /tmp/bench-redis-body.json -w '%{http_code} %{time_total}' "$url" || true)"
  code="$(awk '{print $1}' <<<"$out")"
  t="$(awk '{print $2}' <<<"$out")"
  if [[ "$code" != "200" && "$code" != "201" ]]; then
    echo -e "${RED}  Gagal HTTP $code untuk $url${NC}" >&2
    echo "999999"
    return 1
  fi
  ms_from_sec "$t"
}

measure_http_avg() {
  local url="$1"
  local rounds="$2"
  local total=0
  local i ms
  for ((i=1; i<=rounds; i++)); do
    ms="$(curl_ms "$url")"
    total="$(awk -v a="$total" -v b="$ms" 'BEGIN { printf "%.1f", a + b }')"
  done
  awk -v t="$total" -v n="$rounds" 'BEGIN { printf "%.1f", t / n }'
}

flush_redis() {
  "$SAIL" exec redis redis-cli -n 1 FLUSHDB >/dev/null
}

redis_dbsize() {
  "$SAIL" exec redis redis-cli -n 1 DBSIZE | tr -d '\r'
}

redis_keys_sample() {
  "$SAIL" exec redis redis-cli -n 1 KEYS '*' | tr -d '\r' | sort | head -n 20
}

ORIGINAL_CACHE_STORE="$(grep -E '^CACHE_STORE=' .env | cut -d= -f2- || echo redis)"

set_cache_store() {
  local store="$1"
  if grep -qE '^CACHE_STORE=' .env; then
    sed -i "s/^CACHE_STORE=.*/CACHE_STORE=${store}/" .env
  else
    echo "CACHE_STORE=${store}" >> .env
  fi
  "$SAIL" artisan config:clear >/dev/null 2>&1 || true
}

restore_cache_store() {
  set_cache_store "${ORIGINAL_CACHE_STORE:-redis}"
}

trap restore_cache_store EXIT

# -----------------------------------------------------------------------------
need_cmd curl
need_cmd awk
need_cmd php

if [[ ! -x "$SAIL" && ! -f "$SAIL" ]]; then
  echo -e "${RED}Laravel Sail tidak ditemukan. Jalankan dari root project.${NC}"
  exit 1
fi

title "BENCHMARK REDIS CACHE — SD Negeri Warialau"
echo "Tanggal     : $(date '+%Y-%m-%d %H:%M:%S')"
echo "Base URL    : $BASE_URL"
echo "HTTP rounds : $HTTP_ROUNDS kali per endpoint"
echo "Service iter: $ITERATIONS kali per kasus"
echo -n "Data DB     : "
"$SAIL" artisan tinker --execute="
echo 'berita=' . \App\Models\Berita::where('status','publish')->count()
  . ' | guru_aktif=' . \App\Models\Guru::where('status','aktif')->count()
  . ' | galeri=' . \App\Models\Galeri::count()
  . ' | banner=' . \App\Models\Banner::where('status','aktif')->count();
" 2>/dev/null || echo "(tidak terbaca)"
echo

# Cek Sail + Redis + API
echo -n "Cek Sail/Redis ... "
if ! "$SAIL" exec redis redis-cli ping 2>/dev/null | grep -qi PONG; then
  echo -e "${RED}GAGAL${NC}"
  echo "Jalankan dulu: ./vendor/bin/sail up -d"
  exit 1
fi
echo -e "${GREEN}OK (PONG)${NC}"

echo -n "Cek API        ... "
http_code="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE_URL/api/v1/guru" || true)"
if [[ "$http_code" != "200" ]]; then
  echo -e "${RED}GAGAL (HTTP $http_code)${NC}"
  echo "Pastikan app jalan di $BASE_URL"
  exit 1
fi
echo -e "${GREEN}OK${NC}"

# Ambil ID berita untuk endpoint detail
BERITA_ID="$(curl -sS "$BASE_URL/api/v1/berita" | php -r '
$j = json_decode(stream_get_contents(STDIN), true);
echo $j["data"][0]["id"] ?? "";
')"
if [[ -z "$BERITA_ID" ]]; then
  BERITA_ID="1"
fi

ENDPOINTS=(
  "Guru (500 data)|/api/v1/guru"
  "Berita list 10|/api/v1/berita"
  "Berita list 100|/api/v1/berita?per_page=100"
  "Berita detail|/api/v1/berita/${BERITA_ID}"
  "Profil sekolah|/api/v1/profil-sekolah"
  "Banner (50)|/api/v1/banner"
  "Galeri 12|/api/v1/galeri"
  "Galeri 100|/api/v1/galeri?per_page=100"
  "Info pendaftaran|/api/v1/info-pendaftaran"
)

# =============================================================================
title "1) TEST HTTP ENDPOINT — Cache MISS (DB) vs Cache HIT (Redis)"
echo -e "${DIM}MISS = cache dikosongkan dulu, request harus query database lalu tulis Redis${NC}"
echo -e "${DIM}HIT  = data sudah di Redis, request hanya baca cache${NC}"
echo

printf "%-22s %12s %12s %12s %10s\n" "Endpoint" "MISS (ms)" "HIT Redis" "Selisih" "Lebih cepat"
printf "%-22s %12s %12s %12s %10s\n" "----------------------" "------------" "------------" "------------" "----------"

declare -a HTTP_ROWS=()
sum_miss=0
sum_hit=0
count_ep=0

for item in "${ENDPOINTS[@]}"; do
  label="${item%%|*}"
  path="${item#*|}"
  url="${BASE_URL}${path}"

  # --- MISS: flush sebelum tiap request agar selalu ke DB ---
  miss_total=0
  for ((i=1; i<=HTTP_ROUNDS; i++)); do
    flush_redis
    m="$(curl_ms "$url")"
    miss_total="$(awk -v a="$miss_total" -v b="$m" 'BEGIN { printf "%.1f", a + b }')"
  done
  miss_avg="$(awk -v t="$miss_total" -v n="$HTTP_ROUNDS" 'BEGIN { printf "%.1f", t / n }')"

  # warm sekali
  curl -sS -o /dev/null "$url" || true

  # --- HIT: tanpa flush ---
  hit_avg="$(measure_http_avg "$url" "$HTTP_ROUNDS")"

  diff="$(awk -v a="$miss_avg" -v b="$hit_avg" 'BEGIN { printf "%.1f", a - b }')"
  faster="$(awk -v a="$miss_avg" -v b="$hit_avg" 'BEGIN {
    if (a <= 0) { print "0%"; exit }
    printf "%.0f%%", ((a - b) / a) * 100
  }')"

  printf "%-22s %12s %12s %12s %10s\n" "$label" "$miss_avg" "$hit_avg" "$diff" "$faster"

  sum_miss="$(awk -v a="$sum_miss" -v b="$miss_avg" 'BEGIN { printf "%.1f", a + b }')"
  sum_hit="$(awk -v a="$sum_hit" -v b="$hit_avg" 'BEGIN { printf "%.1f", a + b }')"
  count_ep=$((count_ep + 1))
  HTTP_ROWS+=("$label|$miss_avg|$hit_avg|$faster")
done

avg_miss="$(awk -v t="$sum_miss" -v n="$count_ep" 'BEGIN { printf "%.1f", t / n }')"
avg_hit="$(awk -v t="$sum_hit" -v n="$count_ep" 'BEGIN { printf "%.1f", t / n }')"
avg_faster="$(awk -v a="$avg_miss" -v b="$avg_hit" 'BEGIN {
  if (a <= 0) { print "0%"; exit }
  printf "%.0f%%", ((a - b) / a) * 100
}')"

echo
printf "${BOLD}%-22s %12s %12s %12s %10s${NC}\n" \
  "RATA-RATA" "$avg_miss" "$avg_hit" \
  "$(awk -v a="$avg_miss" -v b="$avg_hit" 'BEGIN { printf "%.1f", a - b }')" \
  "$avg_faster"

echo
echo -e "Arti singkat:"
echo -e "  • ${YELLOW}MISS (ms)${NC}  = waktu tanpa data di Redis (query database)"
echo -e "  • ${GREEN}HIT Redis${NC} = waktu setelah data sudah di Redis"
echo -e "  • Semakin kecil angkanya, semakin cepat"

# =============================================================================
title "1b) TEST HTTP — TANPA CACHE sama sekali (array) vs PAKAI REDIS"
echo -e "${DIM}Mode array = setiap request selalu query DB (tidak menyimpan cache)${NC}"
echo -e "${DIM}Mode redis = request berulang membaca dari Redis${NC}"
echo

HEAVY_ENDPOINTS=(
  "Guru (500)|/api/v1/guru"
  "Berita per100|/api/v1/berita?per_page=100"
  "Galeri per100|/api/v1/galeri?per_page=100"
  "Banner (50)|/api/v1/banner"
)

echo -e "${YELLOW}>> Mode TANPA CACHE (CACHE_STORE=array)...${NC}"
set_cache_store "array"
sleep 1

printf "%-22s %14s %14s %12s %10s\n" "Endpoint" "Tanpa cache" "Pakai Redis" "Selisih" "Lebih cepat"
printf "%-22s %14s %14s %12s %10s\n" "----------------------" "--------------" "--------------" "------------" "----------"

sum_no=0
sum_yes=0
count_h=0
declare -a HEAVY_ROWS=()

for item in "${HEAVY_ENDPOINTS[@]}"; do
  label="${item%%|*}"
  path="${item#*|}"
  url="${BASE_URL}${path}"
  no_avg="$(measure_http_avg "$url" "$HTTP_ROUNDS")"
  HEAVY_ROWS+=("$label|$no_avg")
done

echo -e "${GREEN}>> Mode PAKAI REDIS (CACHE_STORE=redis)...${NC}"
set_cache_store "redis"
flush_redis
sleep 1

# warm semua endpoint berat
for item in "${HEAVY_ENDPOINTS[@]}"; do
  path="${item#*|}"
  curl -sS -o /dev/null "${BASE_URL}${path}" || true
done

idx=0
for item in "${HEAVY_ENDPOINTS[@]}"; do
  label="${item%%|*}"
  path="${item#*|}"
  url="${BASE_URL}${path}"
  no_avg="${HEAVY_ROWS[$idx]#*|}"
  yes_avg="$(measure_http_avg "$url" "$HTTP_ROUNDS")"
  diff="$(awk -v a="$no_avg" -v b="$yes_avg" 'BEGIN { printf "%.1f", a - b }')"
  faster="$(awk -v a="$no_avg" -v b="$yes_avg" 'BEGIN {
    if (a <= 0) { print "0%"; exit }
    printf "%.0f%%", ((a - b) / a) * 100
  }')"
  printf "%-22s %14s %14s %12s %10s\n" "$label" "$no_avg" "$yes_avg" "$diff" "$faster"
  sum_no="$(awk -v a="$sum_no" -v b="$no_avg" 'BEGIN { printf "%.1f", a + b }')"
  sum_yes="$(awk -v a="$sum_yes" -v b="$yes_avg" 'BEGIN { printf "%.1f", a + b }')"
  count_h=$((count_h + 1))
  idx=$((idx + 1))
done

avg_no="$(awk -v t="$sum_no" -v n="$count_h" 'BEGIN { printf "%.1f", t / n }')"
avg_yes="$(awk -v t="$sum_yes" -v n="$count_h" 'BEGIN { printf "%.1f", t / n }')"
avg_yes_faster="$(awk -v a="$avg_no" -v b="$avg_yes" 'BEGIN {
  if (a <= 0) { print "0%"; exit }
  printf "%.0f%%", ((a - b) / a) * 100
}')"

echo
printf "${BOLD}%-22s %14s %14s %12s %10s${NC}\n" \
  "RATA-RATA" "$avg_no" "$avg_yes" \
  "$(awk -v a="$avg_no" -v b="$avg_yes" 'BEGIN { printf "%.1f", a - b }')" \
  "$avg_yes_faster"

restore_cache_store

# =============================================================================
title "2) TEST SERVICE LAYER — Tanpa Cache vs Cache MySQL vs Cache Redis"
echo -e "${DIM}Diukur di dalam PHP (bukan HTTP), rata-rata per pemanggilan (${ITERATIONS}x)${NC}"
echo

SERVICE_JSON="$("$SAIL" php scripts/benchmark-redis.php "$ITERATIONS")"
echo "$SERVICE_JSON" > /tmp/bench-redis-service.json

php -r '
$j = json_decode(file_get_contents("/tmp/bench-redis-service.json"), true);
if (!$j) { fwrite(STDERR, "Gagal parse hasil service benchmark\n"); exit(1); }

if (!empty($j["counts"])) {
    echo "Volume data: berita=" . $j["counts"]["berita_publish"]
        . " | guru_aktif=" . $j["counts"]["guru_aktif"]
        . " | galeri=" . $j["counts"]["galeri"] . PHP_EOL . PHP_EOL;
}

printf("%-24s %12s %14s %12s %14s\n", "Data", "DB (ms)", "Cache MySQL", "Redis (ms)", "Redis vs DB");
printf("%-24s %12s %14s %12s %14s\n", "------------------------", "------------", "--------------", "------------", "--------------");

foreach ($j["rows"] as $r) {
    printf(
        "%-24s %12s %14s %12s %14s\n",
        $r["label"],
        number_format($r["db_ms"], 3, ".", ""),
        number_format($r["db_cache_ms"], 3, ".", ""),
        number_format($r["redis_ms"], 3, ".", ""),
        $r["lebih_cepat_vs_db"]
    );
}

echo PHP_EOL;
printf(
    "%-24s %12s %14s %12s %14s\n",
    "RATA-RATA",
    number_format($j["avg"]["db_ms"], 3, ".", ""),
    number_format($j["avg"]["db_cache_ms"], 3, ".", ""),
    number_format($j["avg"]["redis_ms"], 3, ".", ""),
    $j["avg"]["lebih_cepat_vs_db"]
);

echo PHP_EOL;
echo "Redis lebih cepat dari cache MySQL (rata-rata): " . $j["avg"]["lebih_cepat_vs_db_cache"] . PHP_EOL;
echo "Jumlah key di Redis setelah warm: " . $j["redis_key_count"] . PHP_EOL;
'

# =============================================================================
title "3) BUKTI DATA MASUK REDIS"
echo "DBSIZE (jumlah key di Redis DB 1): $(redis_dbsize)"
echo
echo "Contoh key (maks 20):"
mapfile -t REDIS_KEYS < <(redis_keys_sample)
if [[ ${#REDIS_KEYS[@]} -eq 0 ]]; then
  echo "  (belum ada key — cache kosong)"
else
  for k in "${REDIS_KEYS[@]}"; do
    k="$(echo "$k" | tr -d '\r' | xargs)"
    [[ -z "$k" ]] && continue
    ttl="$("$SAIL" exec redis redis-cli -n 1 TTL "$k" | tr -d '\r')"
    printf "  %-72s TTL=%s detik\n" "$k" "$ttl"
  done
fi

# =============================================================================
title "4) RINGKASAN UNTUK LAPORAN / DEMO DOSEN"
echo -e "Volume data uji: berita=1000 | guru=500 | galeri=500 | banner=50"
echo
echo -e "Endpoint API — MISS vs HIT Redis:"
echo -e "  Cache MISS (query DB)          : ${YELLOW}${avg_miss} ms${NC}"
echo -e "  Cache HIT (Redis)              : ${GREEN}${avg_hit} ms${NC}"
echo -e "  Redis lebih cepat sekitar      : ${BOLD}${avg_faster}${NC}"
echo
echo -e "Endpoint API — TANPA cache vs PAKAI Redis (data berat):"
echo -e "  Tanpa cache (array / selalu DB): ${YELLOW}${avg_no} ms${NC}"
echo -e "  Pakai Redis                    : ${GREEN}${avg_yes} ms${NC}"
echo -e "  Redis lebih cepat sekitar      : ${BOLD}${avg_yes_faster}${NC}"
echo
php -r '
$j = json_decode(file_get_contents("/tmp/bench-redis-service.json"), true);
echo "Service layer (PHP):\n";
echo "  Tanpa cache (langsung DB)     : " . $j["avg"]["db_ms"] . " ms\n";
echo "  Cache database (MySQL)        : " . $j["avg"]["db_cache_ms"] . " ms\n";
echo "  Cache Redis                   : " . $j["avg"]["redis_ms"] . " ms\n";
echo "  Redis lebih cepat vs DB       : " . $j["avg"]["lebih_cepat_vs_db"] . "\n";
echo "  Redis lebih cepat vs MySQL    : " . $j["avg"]["lebih_cepat_vs_db_cache"] . "\n";
'
echo
echo -e "${CYAN}Kesimpulan:${NC} data cache endpoint API tersimpan di Redis, dan pembacaan"
echo "lewat Redis umumnya lebih cepat dibanding query database / cache MySQL."
echo
echo -e "${DIM}Ulangi test:  ./scripts/benchmark-redis.sh${NC}"
echo -e "${DIM}Custom URL:   BASE_URL=http://localhost:8000 ./scripts/benchmark-redis.sh${NC}"
echo -e "${DIM}Lebih teliti: ITERATIONS=100 HTTP_ROUNDS=10 ./scripts/benchmark-redis.sh${NC}"
echo
hr
echo -e "${GREEN}Selesai.${NC}"
