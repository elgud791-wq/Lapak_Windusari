# Lapak Verification Seller - Kecamatan Windusari

Sistem informasi berbasis web untuk pendaftaran dan verifikasi pelaku UMKM di Kecamatan Windusari secara bertingkat (Desa → Kecamatan).

## Fitur Utama

- Pendaftaran UMKM secara online oleh pelaku usaha (tanpa login)
- Generate nomor registrasi otomatis (format: LPW-TAHUN-XXX)
- Verifikasi bertingkat:
    - Admin Desa → status `verified_village`
    - Admin Kecamatan → status `approved` / `rejected`
- Notifikasi otomatis via Email dan WhatsApp (wa.me)
- Multi-role (Admin Desa & Admin Kecamatan)
- Login dilindungi MFA (Google Authenticator)
- Upload foto produk & foto survey
- Export data (PDF & CSV)
- Soft delete untuk menjaga data historis
- Pelacakan status pengajuan oleh pelaku usaha

## Teknologi yang Digunakan

- Backend: Laravel
- Frontend: Blade, Tailwind CSS, Vite
- Database: MySQL / MariaDB
- Authentication: Laravel Breeze + Google2FA (MFA)
- Storage Foto: Cloudinary
- Export: DomPDF, PhpSpreadsheet
- Lainnya: Eloquent ORM, Soft Deletes

## Alur Status Pengajuan

pending → verified_village → approved

atau

pending → rejected_by_village  
verified_village → rejected_by_district

## Instalasi

1. Clone repository
2. Jalankan `composer install` dan `npm install`
3. Salin `.env.example` menjadi `.env` lalu sesuaikan konfigurasi database
4. Jalankan `php artisan key:generate`
5. Jalankan `php artisan migrate --seed`
6. Jalankan `npm run build` lalu `php artisan serve`

Aplikasi berjalan di `http://localhost:8000`

## Cara Menggunakan

**Pelaku Usaha:**

- Buka `/daftar` untuk mendaftar
- Buka `/lacak` untuk melihat status pengajuan

**Admin Desa:**

- Login → verifikasi MFA
- Lakukan verifikasi pengajuan desa masing-masing
- Sistem akan mengirim notifikasi WhatsApp otomatis

**Admin Kecamatan:**

- Login → verifikasi MFA
- Berikan keputusan final (Setujui / Tolak)
- Kelola user Admin Desa
- Export data (PDF / CSV)

## Keamanan

- Authentication (Laravel Breeze)
- Multi-Factor Authentication (Google2FA)
- Role-based Access Control
- Middleware proteksi route
- Soft Deletes

## Lisensi

MIT License
