# Port Configuration untuk Semua Service

Pastikan setiap service berjalan di port yang benar:

## ✅ Port yang Benar:

1. **Gateway Service** → Port **8000** ⚠️
   ```powershell
   php artisan serve --port=8000
   ```

2. **User Service** → Port **8001**
   ```powershell
   php artisan serve --port=8001
   ```

3. **Tabungan Service** → Port **8002**
   ```powershell
   php artisan serve --port=8002
   ```

## ⚠️ Catatan Penting:

- Gateway Service **HARUS** di port **8000** (bukan 8002!)
- User Service di port 8001
- Tabungan Service di port 8002

Saat ini Gateway Service berjalan di port 8002, ini SALAH! Harus di port 8000.

