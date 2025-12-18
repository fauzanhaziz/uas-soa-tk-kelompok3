# Setup .env File untuk Gateway Service

## Buat file .env di folder gateway-service

Buat file `.env` dengan isi berikut:

```env
APP_NAME=GatewayService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

USER_SERVICE_URL=http://localhost:8001
TABUNGAN_SERVICE_URL=http://localhost:8002
```

## Generate APP_KEY

Setelah membuat file .env, jalankan:

```bash
cd gateway-service
php artisan key:generate
```

Ini akan otomatis mengisi APP_KEY di file .env.

## Setelah itu restart service

Stop service yang sedang berjalan (Ctrl+C) dan jalankan lagi:

```bash
php artisan serve --port=8000
```

