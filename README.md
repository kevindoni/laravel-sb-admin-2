# MikroTik Management System

Sistem manajemen MikroTik lengkap dengan billing seperti Mikhmon dan PHPMixBill, dibangun dengan Laravel 12 dan SB Admin 2.

## Fitur Utama

### 🔧 Router Management
- ✅ CRUD manajemen router MikroTik
- ✅ Test koneksi router real-time
- ✅ Monitoring status router (online/offline)
- ✅ Sinkronisasi data user dengan router
- ✅ Penyimpanan informasi sistem router

### 📦 Billing Plans
- ✅ Manajemen paket internet (time-based, data-based, unlimited)
- ✅ Pengaturan bandwidth limit per paket
- ✅ Sistem pricing dan validity period
- ✅ Toggle aktivasi/deaktivasi paket

### 👥 Hotspot User Management
- ✅ CRUD user hotspot dengan sinkronisasi ke MikroTik
- ✅ Batch generation user untuk voucher
- ✅ Monitor session aktif user
- ✅ Disconnect user secara remote
- ✅ Tracking penggunaan data dan waktu

### 🎫 Voucher System
- ✅ Generator voucher dengan kustomisasi kode
- ✅ Batch generation hingga 500 voucher
- ✅ Template voucher dengan preview
- ✅ Aktivasi voucher otomatis
- ✅ Export voucher ke CSV/PDF

### 📊 Monitoring Dashboard
- ✅ Monitoring real-time active users
- ✅ Statistik penggunaan bandwidth
- ✅ Status sistem router (CPU, memory, uptime)
- ✅ Traffic monitoring per user
- ✅ Auto-refresh setiap 30 detik

### 🧾 Transaction & Billing
- ✅ Sistem transaksi dan invoice
- ✅ Top-up balance user
- ✅ Tracking pembayaran manual
- ✅ History transaksi lengkap

### 📈 Reports & Analytics
- ✅ Laporan revenue dan pendapatan
- ✅ Analisis penggunaan data user
- ✅ Top users berdasarkan data/waktu
- ✅ Export data ke CSV
- ✅ Grafik penggunaan harian/bulanan

## Struktur Database

### Tabel Utama:
- `routers` - Data koneksi router MikroTik
- `billing_plans` - Paket internet dan pricing
- `hotspot_users` - User hotspot dengan relasi ke router dan paket
- `vouchers` - Voucher codes dengan batch management
- `transactions` - Transaksi billing dan pembayaran
- `user_sessions` - Log session aktif user untuk monitoring

## Requirements

- PHP >= 8.2
- Laravel 12
- MySQL/PostgreSQL Database
- MikroTik RouterOS v6.x atau v7.x dengan API aktif
- Akses jaringan ke router MikroTik

## Installation

1. Clone repository dan install dependencies:
```bash
composer install
```

2. Setup environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Konfigurasi database di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mikrotik_management
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. Jalankan migrasi database:
```bash
php artisan migrate
```

5. Seed data awal (opsional):
```bash
php artisan db:seed
```

6. Jalankan aplikasi:
```bash
php artisan serve
```

## Konfigurasi MikroTik

Pastikan API service aktif di router MikroTik:

```
/ip service enable api
/ip service set api port=8728
```

Buat user untuk akses API:
```
/user add name=apiuser password=apipass group=full
```

## Fitur Mendatang

- 🔄 Payment gateway integration (Midtrans, Xendit, dll)
- 🔄 User portal untuk end-user
- 🔄 WhatsApp notification
- 🔄 SMS gateway integration
- 🔄 Advanced reporting dengan chart
- 🔄 Backup/restore konfigurasi
- 🔄 Multi-tenant support

## Penggunaan

1. **Tambah Router**: Masuk ke Router Management → Tambah Router
2. **Buat Paket**: Masuk ke Billing Plans → Tambah Paket
3. **Generate Voucher**: Masuk ke Vouchers → Generate Voucher
4. **Monitor Network**: Masuk ke Monitoring untuk melihat user aktif
5. **Lihat Laporan**: Masuk ke Reports untuk analytics

## API Integration

Aplikasi ini menggunakan MikroTik RouterOS API untuk:
- Manajemen user hotspot
- Monitoring session aktif
- Disconnect user remote
- Ambil informasi sistem router
- Sinkronisasi data real-time

## Security Notes

- Password router di-encrypt di database
- CSRF protection untuk semua form
- Authentication required untuk semua fitur
- Input validation pada semua controller

## License

MIT License - Bebas digunakan untuk project komersial maupun personal.

## Credits

- Laravel Framework
- SB Admin 2 Bootstrap Template
- MikroTik RouterOS API
- FontAwesome Icons

---

**Developed for MikroTik Hotspot Management**  
Sistem billing dan manajemen voucher yang powerful untuk ISP dan warnet.
