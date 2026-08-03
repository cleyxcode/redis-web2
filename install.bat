@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

:: ═══════════════════════════════════════════════════════════════════════════════
::  INSTALL SCRIPT — Sistem Informasi SD Negeri Warialau
::  Platform: Windows (CMD)
::  Kebutuhan: Docker Desktop for Windows
::  Menjalankan aplikasi via Laravel Sail
:: ═══════════════════════════════════════════════════════════════════════════════

title SD Negeri Warialau — Laravel Sail Installer

set SAIL=vendor\bin\sail

echo.
echo  ╔══════════════════════════════════════════════════════╗
echo  ║     Sistem Informasi SD Negeri Warialau              ║
echo  ║     Laravel Sail Installer — Windows                 ║
echo  ╚══════════════════════════════════════════════════════╝
echo.

:: ── Cek Docker ────────────────────────────────────────────────────────────────
echo [1/5] Memeriksa instalasi Docker...
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  [GAGAL] Docker tidak ditemukan!
    echo  Download dari: https://www.docker.com/products/docker-desktop/
    pause
    exit /b 1
)
echo  [OK] Docker ditemukan
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo  [GAGAL] Docker Desktop belum berjalan. Buka Docker Desktop terlebih dahulu!
    pause
    exit /b 1
)
echo  [OK] Docker Desktop aktif
echo.

:: ── Setup .env ────────────────────────────────────────────────────────────────
echo [2/5] Menyiapkan file konfigurasi .env...
if not exist ".env" (
    if exist ".env.example" (
        copy .env.example .env >nul
        echo  [OK] .env dibuat dari .env.example
    ) else (
        echo  [GAGAL] File .env.example tidak ditemukan!
        pause
        exit /b 1
    )
) else (
    echo  [OK] .env sudah ada, melewati langkah ini
)

powershell -Command "(Get-Content .env) -replace 'DB_HOST=.*','DB_HOST=mysql' -replace 'REDIS_HOST=.*','REDIS_HOST=redis' -replace 'MAIL_HOST=.*','MAIL_HOST=mailpit' -replace 'DB_USERNAME=.*','DB_USERNAME=sail' -replace 'DB_PASSWORD=.*','DB_PASSWORD=password' | Set-Content .env"
findstr /B "APP_PORT=" .env >nul 2>&1 || echo APP_PORT=8000>> .env
findstr /B "WWWUSER=" .env >nul 2>&1 || (
    echo WWWUSER=1000>> .env
    echo WWWGROUP=1000>> .env
)
echo  [OK] Konfigurasi .env disesuaikan untuk Laravel Sail
echo.

:: ── Composer install jika vendor belum ada ────────────────────────────────────
echo [3/5] Memastikan Laravel Sail tersedia...
if not exist "vendor\bin\sail" (
    where composer >nul 2>&1
    if %errorlevel% neq 0 (
        echo  [GAGAL] Composer tidak ditemukan dan vendor\ belum ada.
        pause
        exit /b 1
    )
    composer install
    if %errorlevel% neq 0 (
        echo  [GAGAL] composer install gagal!
        pause
        exit /b 1
    )
)
echo  [OK] Laravel Sail tersedia
echo.

:: ── Build & jalankan Sail ─────────────────────────────────────────────────────
echo [4/5] Build & menjalankan Laravel Sail (pertama kali bisa lama)...
call %SAIL% up -d --build
if %errorlevel% neq 0 (
    echo  [GAGAL] Gagal menjalankan Sail!
    pause
    exit /b 1
)
echo  [OK] Container Sail berjalan
call %SAIL% ps
echo.

:: ── Setup Laravel ─────────────────────────────────────────────────────────────
echo [5/5] Setup Laravel...
echo  Menunggu MySQL siap (8 detik)...
timeout /t 8 /nobreak >nul

echo  - Generate APP_KEY (jika belum)...
call %SAIL% artisan key:generate --force
echo  [OK] APP_KEY

echo  - Menjalankan migration...
call %SAIL% artisan migrate --force
echo  [OK] Migration selesai

echo  - Menjalankan seeder...
call %SAIL% artisan db:seed --force
echo  [OK] Seeder selesai

echo  - Membuat storage symlink...
call %SAIL% artisan storage:link
echo  [OK] Storage symlink dibuat
echo.

:: ── Selesai ───────────────────────────────────────────────────────────────────
echo  ╔══════════════════════════════════════════════════════╗
echo  ║            INSTALASI BERHASIL!                       ║
echo  ╚══════════════════════════════════════════════════════╝
echo.
echo   Website   : http://localhost:8000
echo   Admin     : http://localhost:8000/admin
echo   Mailpit   : http://localhost:8025
echo   Email     : admin@admin.com
echo   Password  : admin
echo.
echo   PERINGATAN: Ganti password admin setelah login pertama!
echo.
echo   Perintah berguna (Laravel Sail):
echo     vendor\bin\sail up -d       -- jalankan container
echo     vendor\bin\sail stop        -- hentikan container
echo     vendor\bin\sail artisan ... -- perintah Artisan
echo     vendor\bin\sail shell       -- masuk ke container
echo     vendor\bin\sail logs -f     -- lihat log
echo     vendor\bin\sail down -v     -- hapus container dan volume
echo.
pause
