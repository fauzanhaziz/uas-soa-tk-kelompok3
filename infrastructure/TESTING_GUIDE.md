# Panduan Testing di Thunder Client

## 📋 Persiapan

### 1. Pastikan Semua Service Berjalan

Buka 3 terminal terpisah dan jalankan:

**Terminal 1 - Gateway Service:**
```bash
cd gateway-service
php artisan serve --port=8000
```

**Terminal 2 - User Service:**
```bash
cd user-service/auth-service-dit
php artisan serve --port=8001
```

**Terminal 3 - Tabungan Service:**
```bash
cd tabungan-service
php artisan serve --port=8002
```

### 2. Setup Environment Variables

Pastikan file `.env` di setiap service sudah dikonfigurasi:

**Gateway Service (.env):**
```env
USER_SERVICE_URL=http://localhost:8001
TABUNGAN_SERVICE_URL=http://localhost:8002
```

## 🧪 Testing di Thunder Client

### Test 1: Register User (User Service)

**Request:**
- **Method:** `POST`
- **URL:** `http://localhost:8001/api/register`
- **Headers:**
  ```
  Content-Type: application/json
  X-Correlation-ID: test-correlation-001
  ```
- **Body (JSON):**
  ```json
  {
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```

**Expected Response:**
- Status: `201 Created`
- Response Headers: `X-Correlation-ID: test-correlation-001`
- Response Body: User data dengan message "Register berhasil"

**Verifikasi:**
1. Cek response header `X-Correlation-ID` = `test-correlation-001`
2. Cek log file: `user-service/auth-service-dit/storage/logs/laravel.log`
   - Harus ada log dengan correlation_id: `test-correlation-001`

---

### Test 2: Login (User Service)

**Request:**
- **Method:** `POST`
- **URL:** `http://localhost:8001/api/login`
- **Headers:**
  ```
  Content-Type: application/json
  X-Correlation-ID: test-correlation-002
  ```
- **Body (JSON):**
  ```json
  {
    "email": "test@example.com",
    "password": "password123"
  }
  ```

**Expected Response:**
- Status: `200 OK`
- Response Headers: `X-Correlation-ID: test-correlation-002`
- Response Body: Token JWT

**Verifikasi:**
1. Simpan token dari response untuk test berikutnya
2. Cek response header `X-Correlation-ID`
3. Cek log file untuk melihat tracing

---

### Test 3: Get User by ID (User Service)

**Request:**
- **Method:** `GET`
- **URL:** `http://localhost:8001/api/users/1`
- **Headers:**
  ```
  Authorization: Bearer <token_dari_test_2>
  X-Correlation-ID: test-correlation-003
  ```

**Expected Response:**
- Status: `200 OK`
- Response Headers: `X-Correlation-ID: test-correlation-003`
- Response Body: User data

**Verifikasi:**
1. Cek response header `X-Correlation-ID`
2. Cek log file untuk melihat log dengan correlation_id yang sama

---

### Test 4: Create Tabungan Transaction (Tabungan Service)

**Request:**
- **Method:** `POST`
- **URL:** `http://localhost:8002/api/tabungan`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer <token>
  X-Correlation-ID: test-correlation-004
  ```
- **Body (JSON):**
  ```json
  {
    "id_siswa": 1,
    "nominal": 50000,
    "jenis_transaksi": "masuk",
    "tanggal": "2025-01-15"
  }
  ```

**Expected Response:**
- Status: `201 Created`
- Response Headers: `X-Correlation-ID: test-correlation-004`
- Response Body: Transaction data

**Verifikasi:**
1. Cek response header `X-Correlation-ID`
2. Cek log file: `tabungan-service/storage/logs/laravel.log`

---

### Test 5: Get Tabungan by Siswa (Tabungan Service)

**Request:**
- **Method:** `GET`
- **URL:** `http://localhost:8002/api/tabungan/siswa/1`
- **Headers:**
  ```
  Authorization: Bearer <token>
  X-Correlation-ID: test-correlation-005
  ```

**Expected Response:**
- Status: `200 OK`
- Response Headers: `X-Correlation-ID: test-correlation-005`
- Response Body: Tabungan data untuk siswa ID 1

**Verifikasi:**
1. Cek response header `X-Correlation-ID`
2. Cek log file untuk tracing

---

### Test 6: Gateway - Get Siswa Detail (Aggregator)

**Request:**
- **Method:** `GET`
- **URL:** `http://localhost:8000/api/gateway/siswa/1`
- **Headers:**
  ```
  Authorization: Bearer <token>
  X-Correlation-ID: test-correlation-006
  ```

**Expected Response:**
- Status: `200 OK`
- Response Headers: `X-Correlation-ID: test-correlation-006`
- Response Body:
  ```json
  {
    "status": "success",
    "data": {
      "siswa": { ... },
      "tabungan": { ... }
    }
  }
  ```

**Verifikasi:**
1. Cek response header `X-Correlation-ID` = `test-correlation-006`
2. Cek log files di **SEMUA** service:
   - `gateway-service/storage/logs/laravel.log`
   - `user-service/auth-service-dit/storage/logs/laravel.log`
   - `tabungan-service/storage/logs/laravel.log`
3. Cari `test-correlation-006` di semua log files - harus ada!

---

### Test 7: Test Auto-Generate Correlation ID

**Request:**
- **Method:** `GET`
- **URL:** `http://localhost:8000/api/gateway/siswa/1`
- **Headers:**
  ```
  Authorization: Bearer <token>
  (TIDAK mengirim X-Correlation-ID)
  ```

**Expected Response:**
- Status: `200 OK`
- Response Headers: `X-Correlation-ID: <UUID_baru>`
  - Middleware akan auto-generate UUID baru

**Verifikasi:**
1. Cek response header `X-Correlation-ID` - harus ada UUID
2. Copy UUID tersebut
3. Cek log files - UUID yang sama harus ada di semua service

---

## 🔍 Cara Verifikasi Logs

### 1. Buka Log Files

**Gateway Service:**
```bash
tail -f gateway-service/storage/logs/laravel.log
```

**User Service:**
```bash
tail -f user-service/auth-service-dit/storage/logs/laravel.log
```

**Tabungan Service:**
```bash
tail -f tabungan-service/storage/logs/laravel.log
```

### 2. Cari Correlation ID di Logs

Gunakan grep untuk mencari Correlation ID:

```bash
# Di Gateway Service
grep "test-correlation-006" gateway-service/storage/logs/laravel.log

# Di User Service
grep "test-correlation-006" user-service/auth-service-dit/storage/logs/laravel.log

# Di Tabungan Service
grep "test-correlation-006" tabungan-service/storage/logs/laravel.log
```

### 3. Contoh Log Output yang Diharapkan

**Gateway Service Log:**
```
[2025-01-XX XX:XX:XX] local.INFO: Incoming request {"method":"GET","url":"http://localhost:8000/api/gateway/siswa/1","ip":"127.0.0.1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Processing siswa detail request {"siswa_id":"1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Calling User Service {"url":"http://localhost:8001/api/siswa/1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Calling Tabungan Service {"url":"http://localhost:8002/api/tabungan/siswa/1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Gateway: Successfully aggregated data {"siswa_id":"1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Outgoing response {"status":200,"correlation_id":"test-correlation-006"}
```

**User Service Log:**
```
[2025-01-XX XX:XX:XX] local.INFO: Incoming request {"method":"GET","url":"http://localhost:8001/api/siswa/1","ip":"127.0.0.1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: User Service: Fetching user by ID {"user_id":"1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: User Service: User found {"user_id":"1","user_name":"Test User","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Outgoing response {"status":200,"correlation_id":"test-correlation-006"}
```

**Tabungan Service Log:**
```
[2025-01-XX XX:XX:XX] local.INFO: Incoming request {"method":"GET","url":"http://localhost:8002/api/tabungan/siswa/1","ip":"127.0.0.1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Tabungan Service: Fetching tabungan for student {"id_siswa":"1","correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Tabungan Service: Tabungan data retrieved {"id_siswa":"1","transaction_count":1,"saldo":50000,"correlation_id":"test-correlation-006"}
[2025-01-XX XX:XX:XX] local.INFO: Outgoing response {"status":200,"correlation_id":"test-correlation-006"}
```

## ✅ Checklist Testing

- [ ] Test 1: Register User - Correlation ID ada di response header
- [ ] Test 2: Login - Token berhasil didapat
- [ ] Test 3: Get User - Correlation ID ter-forward
- [ ] Test 4: Create Tabungan - Correlation ID ter-log
- [ ] Test 5: Get Tabungan - Correlation ID ter-log
- [ ] Test 6: Gateway Aggregator - Correlation ID sama di semua service logs
- [ ] Test 7: Auto-generate Correlation ID - UUID baru ter-generate
- [ ] Verifikasi: Correlation ID sama muncul di semua log files

## 🎯 Poin Penting untuk Demo

1. **Correlation ID Consistency**: Pastikan Correlation ID yang sama muncul di semua service logs
2. **Request Tracing**: Dapat melacak request dari Gateway → User Service → Tabungan Service
3. **Auto-Generation**: Jika tidak kirim Correlation ID, middleware auto-generate
4. **Response Header**: Correlation ID selalu dikembalikan di response header

## 🐛 Troubleshooting

### Problem: Correlation ID tidak muncul di log
**Solution:** Pastikan middleware sudah ter-register di `bootstrap/app.php`

### Problem: Correlation ID tidak ter-forward ke downstream service
**Solution:** Cek `GatewayController.php` - pastikan header `X-Correlation-ID` ditambahkan

### Problem: Log tidak muncul
**Solution:** 
- Cek permission folder `storage/logs`
- Pastikan `LOG_CHANNEL=single` di `.env`
- Clear cache: `php artisan config:clear`

### Problem: Service tidak bisa diakses
**Solution:**
- Pastikan semua service berjalan di port yang berbeda
- Cek firewall/antivirus tidak block port
- Pastikan `.env` sudah dikonfigurasi dengan benar

