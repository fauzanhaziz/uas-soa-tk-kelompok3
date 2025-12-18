# Setup dan Testing Guide

## ✅ Dependencies Sudah Terinstall

Semua dependencies sudah terinstall untuk:
- ✅ Gateway Service
- ✅ Tabungan Service  
- ✅ User Service

## 🚀 Cara Menjalankan Service

Buka **3 terminal terpisah** dan jalankan:

### Terminal 1 - Gateway Service (Port 8000)
```bash
cd "C:\Users\alfar\Downloads\uas-soa-tk-kelompok3-main (2)\uas-soa-tk-kelompok3-main\gateway-service"
php artisan serve --port=8000
```

### Terminal 2 - User Service (Port 8001)
```bash
cd "C:\Users\alfar\Downloads\uas-soa-tk-kelompok3-main (2)\uas-soa-tk-kelompok3-main\user-service\auth-service-dit"
php artisan serve --port=8001
```

### Terminal 3 - Tabungan Service (Port 8002)
```bash
cd "C:\Users\alfar\Downloads\uas-soa-tk-kelompok3-main (2)\uas-soa-tk-kelompok3-main\tabungan-service"
php artisan serve --port=8002
```

## 🧪 Testing di Thunder Client

### Test 1: Register User

**Request:**
- Method: `POST`
- URL: `http://localhost:8001/api/register`
- Headers:
  ```
  Content-Type: application/json
  X-Correlation-ID: test-001
  ```
- Body:
  ```json
  {
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```

**Expected:**
- Status: `201 Created`
- Response Header: `X-Correlation-ID: test-001`

### Test 2: Login

**Request:**
- Method: `POST`
- URL: `http://localhost:8001/api/login`
- Headers:
  ```
  Content-Type: application/json
  X-Correlation-ID: test-002
  ```
- Body:
  ```json
  {
    "email": "test@example.com",
    "password": "password123"
  }
  ```

**Expected:**
- Status: `200 OK`
- Response Body: Token JWT (simpan untuk test berikutnya)
- Response Header: `X-Correlation-ID: test-002`

### Test 3: Gateway Aggregator (PENTING untuk Demo!)

**Request:**
- Method: `GET`
- URL: `http://localhost:8000/api/gateway/siswa/1`
- Headers:
  ```
  Authorization: Bearer <token_dari_test_2>
  X-Correlation-ID: test-003
  ```

**Expected:**
- Status: `200 OK`
- Response Header: `X-Correlation-ID: test-003`
- Response Body: Data siswa dan tabungan

### Test 4: Verifikasi Logs

Setelah test 3, cek log files di ketiga service. Correlation ID `test-003` harus muncul di **SEMUA** log files:

**Gateway Service:**
```bash
# Buka file atau gunakan grep
notepad gateway-service\storage\logs\laravel.log
# atau
grep "test-003" gateway-service\storage\logs\laravel.log
```

**User Service:**
```bash
grep "test-003" user-service\auth-service-dit\storage\logs\laravel.log
```

**Tabungan Service:**
```bash
grep "test-003" tabungan-service\storage\logs\laravel.log
```

Correlation ID yang sama (`test-003`) harus muncul di **ketiga** log files ini!

## 📝 Checklist untuk Demo

- [ ] Semua 3 service berjalan
- [ ] Test Register - Correlation ID ada di response header
- [ ] Test Login - Dapat token
- [ ] Test Gateway - Correlation ID sama muncul di semua service logs
- [ ] Screenshot response header dengan Correlation ID
- [ ] Screenshot logs dari ketiga service dengan Correlation ID yang sama

## 🎯 Poin Penting untuk Ditunjukkan

1. **Correlation ID Consistency**: Correlation ID yang sama muncul di Gateway → User Service → Tabungan Service
2. **Request Tracing**: Dapat melacak 1 request dari awal hingga akhir menggunakan Correlation ID
3. **Auto-Generation**: Jika tidak kirim Correlation ID, middleware auto-generate UUID baru

## 📚 Dokumentasi Lengkap

Untuk panduan lebih detail, lihat:
- `infrastructure/README.md` - Dokumentasi lengkap implementasi
- `infrastructure/TESTING_GUIDE.md` - Panduan testing detail

