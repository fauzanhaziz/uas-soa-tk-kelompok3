# Middleware & Infrastructure Documentation

## Overview

Implementasi middleware Correlation ID dan distributed logging untuk sistem manajemen sekolah terintegrasi. Middleware ini memastikan setiap request memiliki Correlation ID yang unik yang dapat ditelusuri di seluruh service (Gateway, User Service, dan Tabungan Service).

## Fitur

1. **Correlation ID Middleware** - Menangani Correlation ID untuk setiap request
2. **Distributed Logging** - Logging dengan context yang konsisten di semua service
3. **Request Tracing** - Kemampuan untuk melacak request dari gateway hingga service terakhir

## Arsitektur

```
Client Request
    ↓
Gateway Service (CorrelationIdMiddleware)
    ↓ (forward X-Correlation-ID header)
User Service (CorrelationIdMiddleware)
Tabungan Service (CorrelationIdMiddleware)
    ↓
Logs dengan Correlation ID
```

## Implementasi

### 1. Correlation ID Middleware

Middleware ini diimplementasikan di semua service:
- `gateway-service/app/Http/Middleware/CorrelationIdMiddleware.php`
- `tabungan-service/app/Http/Middleware/CorrelationIdMiddleware.php`
- `user-service/auth-service-dit/app/Http/Middleware/CorrelationIdMiddleware.php`

**Fungsi:**
- Mengambil Correlation ID dari header `X-Correlation-ID` jika ada
- Jika tidak ada, generate UUID baru
- Menambahkan Correlation ID ke request attributes
- Menambahkan Correlation ID ke logging context
- Menambahkan Correlation ID ke response header
- Logging incoming request dan outgoing response

### 2. Custom Log Processor

Processor untuk memastikan Correlation ID selalu ada di log:
- `gateway-service/app/Logging/CorrelationIdProcessor.php`
- `tabungan-service/app/Logging/CorrelationIdProcessor.php`
- `user-service/auth-service-dit/app/Logging/CorrelationIdProcessor.php`

**Fungsi:**
- Menambahkan Correlation ID ke setiap log record
- Mengambil Correlation ID dari request header atau context

### 3. Logging Configuration

Konfigurasi logging diupdate di semua service untuk menggunakan custom processor:
- `config/logging.php` - Channel 'single' menggunakan `CorrelationIdProcessor`

### 4. Gateway Controller Update

GatewayController diupdate untuk:
- Meneruskan Correlation ID ke downstream services (User Service & Tabungan Service)
- Logging setiap langkah proses aggregasi
- Error handling dengan logging yang proper

## Cara Kerja

### Flow Request dengan Correlation ID

1. **Client mengirim request ke Gateway**
   ```
   GET /api/gateway/siswa/1
   Headers: Authorization: Bearer <token>
   ```

2. **Gateway Service**
   - Middleware menangkap request
   - Generate Correlation ID (jika tidak ada di header)
   - Set Correlation ID ke context logging
   - Log: "Incoming request" dengan Correlation ID
   - Forward request ke User Service & Tabungan Service dengan header `X-Correlation-ID`
   - Log: "Calling User Service" dengan Correlation ID
   - Log: "Calling Tabungan Service" dengan Correlation ID
   - Aggregate response
   - Log: "Successfully aggregated data" dengan Correlation ID
   - Return response dengan header `X-Correlation-ID`

3. **User Service & Tabungan Service**
   - Middleware menangkap request
   - Ambil Correlation ID dari header `X-Correlation-ID`
   - Set Correlation ID ke context logging
   - Log: "Incoming request" dengan Correlation ID
   - Process request
   - Log: "User Service: Fetching user by ID" dengan Correlation ID
   - Return response dengan header `X-Correlation-ID`

### Contoh Log Output

**Gateway Service Log:**
```
[2025-01-XX XX:XX:XX] local.INFO: Incoming request {"method":"GET","url":"http://localhost:8000/api/gateway/siswa/1","ip":"127.0.0.1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Processing siswa detail request {"siswa_id":"1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Calling User Service {"url":"http://localhost:8001/api/users/1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Calling Tabungan Service {"url":"http://localhost:8002/api/tabungan/siswa/1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Successfully aggregated data {"siswa_id":"1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Outgoing response {"status":200,"correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
```

**User Service Log:**
```
[2025-01-XX XX:XX:XX] local.INFO: Incoming request {"method":"GET","url":"http://localhost:8001/api/users/1","ip":"127.0.0.1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: User Service: Fetching user by ID {"user_id":"1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: User Service: User found {"user_id":"1","user_name":"John Doe","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Outgoing response {"status":200,"correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
```

**Tabungan Service Log:**
```
[2025-01-XX XX:XX:XX] local.INFO: Incoming request {"method":"GET","url":"http://localhost:8002/api/tabungan/siswa/1","ip":"127.0.0.1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Tabungan Service: Checking balance for student {"id_siswa":"1","correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Tabungan Service: Balance retrieved {"id_siswa":"1","saldo":50000,"correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
[2025-01-XX XX:XX:XX] local.INFO: Outgoing response {"status":200,"correlation_id":"550e8400-e29b-41d4-a716-446655440000"}
```

## Testing dengan Thunder Client

### 1. Test Correlation ID di Gateway

**Request:**
```
GET http://localhost:8000/api/gateway/siswa/1
Headers:
  Authorization: Bearer <your_token>
  X-Correlation-ID: test-correlation-id-123 (optional)
```

**Response Headers:**
```
X-Correlation-ID: test-correlation-id-123 (atau UUID baru jika tidak dikirim)
```

### 2. Test Correlation ID di User Service

**Request:**
```
GET http://localhost:8001/api/users/1
Headers:
  Authorization: Bearer <your_token>
  X-Correlation-ID: test-correlation-id-123
```

**Response Headers:**
```
X-Correlation-ID: test-correlation-id-123
```

### 3. Test Correlation ID di Tabungan Service

**Request:**
```
GET http://localhost:8002/api/tabungan
Headers:
  Authorization: Bearer <your_token>
  X-Correlation-ID: test-correlation-id-123
```

**Response Headers:**
```
X-Correlation-ID: test-correlation-id-123
```

### 4. Verifikasi Logging

Setelah melakukan request, cek log files:
- `gateway-service/storage/logs/laravel.log`
- `tabungan-service/storage/logs/laravel.log`
- `user-service/auth-service-dit/storage/logs/laravel.log`

Cari Correlation ID yang sama di semua log files untuk melihat tracing request.

## File yang Dibuat/Dimodifikasi

### Gateway Service
- ✅ `app/Http/Middleware/CorrelationIdMiddleware.php` (updated)
- ✅ `app/Logging/CorrelationIdProcessor.php` (new)
- ✅ `app/Http/Controllers/GatewayController.php` (updated)
- ✅ `bootstrap/app.php` (updated - register middleware)
- ✅ `config/logging.php` (updated - add processor)

### Tabungan Service
- ✅ `app/Http/Middleware/CorrelationIdMiddleware.php` (new)
- ✅ `app/Logging/CorrelationIdProcessor.php` (new)
- ✅ `app/Http/Controllers/TabunganController.php` (updated - add logging)
- ✅ `bootstrap/app.php` (updated - register middleware)
- ✅ `config/logging.php` (updated - add processor)

### User Service
- ✅ `app/Http/Middleware/CorrelationIdMiddleware.php` (new)
- ✅ `app/Logging/CorrelationIdProcessor.php` (new)
- ✅ `app/Http/Controllers/UserController.php` (updated - add logging)
- ✅ `app/Http/Controllers/AuthController.php` (updated - add logging)
- ✅ `bootstrap/app.php` (updated - register middleware)
- ✅ `config/logging.php` (updated - add processor)

## Manfaat

1. **Distributed Tracing** - Dapat melacak request dari gateway hingga service terakhir menggunakan Correlation ID yang sama
2. **Debugging** - Memudahkan debugging dengan mencari Correlation ID di semua log files
3. **Monitoring** - Dapat memonitor flow request di seluruh sistem
4. **Error Tracking** - Ketika terjadi error, dapat dengan mudah melacak di service mana error terjadi dengan Correlation ID yang sama

## Catatan Penting

1. **Correlation ID Header**: Semua service menggunakan header `X-Correlation-ID` untuk meneruskan Correlation ID
2. **Auto Generation**: Jika request tidak memiliki Correlation ID, middleware akan generate UUID baru
3. **Log Context**: Correlation ID otomatis ditambahkan ke semua log entries melalui custom processor
4. **Response Header**: Correlation ID selalu dikembalikan di response header untuk debugging

## Kontributor

**Muhammad Alfaridsi Kardafi** - Middleware & Infrastructure Implementation

