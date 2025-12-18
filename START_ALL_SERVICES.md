# Cara Menjalankan Semua Service

Anda perlu menjalankan **3 service secara bersamaan** di **3 terminal terpisah**:

## Terminal 1 - Gateway Service (Port 8000)
```powershell
cd "C:\Users\alfar\Downloads\uas-soa-tk-kelompok3-main (2)\uas-soa-tk-kelompok3-main\gateway-service"
php artisan serve --port=8000
```

## Terminal 2 - User Service (Port 8001) ⚠️ BELUM BERJALAN
```powershell
cd "C:\Users\alfar\Downloads\uas-soa-tk-kelompok3-main (2)\uas-soa-tk-kelompok3-main\user-service\auth-service-dit"
php artisan serve --port=8001
```

## Terminal 3 - Tabungan Service (Port 8002) ⚠️ BELUM BERJALAN
```powershell
cd "C:\Users\alfar\Downloads\uas-soa-tk-kelompok3-main (2)\uas-soa-tk-kelompok3-main\tabungan-service"
php artisan serve --port=8002
```

## Checklist

Pastikan ketiga service berjalan:
- [x] Gateway Service (port 8000) - ✅ Sudah berjalan
- [ ] User Service (port 8001) - ❌ Belum berjalan
- [ ] Tabungan Service (port 8002) - ❌ Belum berjalan

Setelah ketiga service berjalan, coba lagi request di Thunder Client!

