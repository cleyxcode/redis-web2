# Setup FCM (Push Notification)

Push FCM bersifat **opsional**. Tanpa kredensial, notifikasi in-app + polling mobile tetap jalan.

## Backend (Laravel)

1. Buat project di [Firebase Console](https://console.firebase.google.com/).
2. Project settings → Service accounts → Generate new private key.
3. Simpan file JSON ke `storage/app/firebase-credentials.json` (jangan commit).
4. Isi `.env`:

```env
FCM_PROJECT_ID=your-firebase-project-id
FCM_CREDENTIALS=/var/www/html/storage/app/firebase-credentials.json
```

5. Pastikan queue worker jalan (`php artisan queue:work`) agar `SendFcmPushJob` terkirim.

## Mobile (Flutter)

Ikuti `mobile_apps_sd/docs/FCM_SETUP.md` — tambahkan `google-services.json` dan aktifkan plugin Google Services.

## Alur

1. User login di app → token FCM dikirim ke `POST /api/v1/device-tokens`.
2. Admin verifikasi pendaftaran → `Notifikasi` + job FCM ke device orang tua.
3. Logout → `DELETE /api/v1/device-tokens`.
