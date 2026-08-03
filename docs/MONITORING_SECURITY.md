# Monitoring & keamanan

## Health check
- Laravel built-in: `GET /up`
- Detail API: `GET /api/v1/health` — cek DB, cache, queue

## Sentry (opsional)

### Web
```env
SENTRY_LARAVEL_DSN=https://xxx@sentry.io/yyy
SENTRY_TRACES_SAMPLE_RATE=0.2
```

### Mobile
Jalankan dengan:
```bash
flutter run --dart-define=SENTRY_DSN=https://xxx@sentry.io/yyy
```

Tanpa DSN, Sentry di-skip (aman untuk local).

## CORS
```env
CORS_ALLOWED_ORIGINS=https://domain-sekolah.id,http://localhost:8000
```

Jangan pakai `*` di production.

## Rate limit API
| Endpoint | Limit |
|---|---|
| login / register / reset | 5/menit / IP |
| OTP | 3/menit / email |
| POST pendaftaran | 3/menit / user |
| API umum | 60/menit |

## Deep link notifikasi (mobile)
Tap notifikasi (lokal atau FCM) → detail berita / pendaftaran setelah splash selesai.
