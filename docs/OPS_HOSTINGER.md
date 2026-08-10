# Operasional Hostinger (Queue, Cron, FCM, Backup)

Deploy kode dulu, lalu jalankan langkah di bawah di panel Hostinger / SSH.

## 1. Cron wajib (dua baris)

Ganti `/path/to/web_laravel_sd` dengan path `public_html` / project Laravel Anda.

```cron
* * * * * cd /path/to/web_laravel_sd && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/web_laravel_sd && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

- Baris 1: backup harian/mingguan, prune failed jobs, monitor backup.
- Baris 2: memproses job FCM + email (aman untuk shared hosting).

Cek status:
```bash
php artisan system:status
```

## 2. FCM (push notifikasi)

1. Buat project Firebase → Cloud Messaging.
2. Download service account JSON → simpan sebagai `storage/app/firebase-credentials.json` (jangan commit).
3. Di `.env`:
```env
FCM_PROJECT_ID=your-firebase-project-id
FCM_CREDENTIALS=/path/to/web_laravel_sd/storage/app/firebase-credentials.json
```
4. Uji:
```bash
php artisan fcm:test
php artisan fcm:test {user_id}
```

## 3. Backup Spatie

Sudah terjadwal di `routes/console.php`:
- `01:00` clean
- `01:30` backup DB
- `Minggu 02:00` backup penuh
- `03:00` monitor

Pastikan disk backup di `config/backup.php` writable.

## 4. Sentry (opsional)

```env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=0.2
```

Mobile:
```bash
flutter build apk --dart-define=SENTRY_DSN=https://...@sentry.io/... --dart-define=API_BASE_HOST=https://sdnegeriwailau.site
```

## 5. Domain resmi APK

```bash
flutter build apk --release --dart-define=API_BASE_HOST=https://sdnegeriwailau.site
```

Default lokal/APK lama masih mengarah ke Hostinger staging.

## 6. Backfill notifikasi lama

```bash
php artisan notifikasi:backfill-pendaftaran --dry-run
php artisan notifikasi:backfill-pendaftaran
```

## 7. Health check

- `GET /up`
- `GET /api/v1/health` → cek `checks.queue`, `checks.fcm`, `meta.queue_pending`
