# QR Generate

Aplikasi web untuk membuat dan mengelola kode QR. Halaman utama menampilkan daftar proyek. Form pembuatan ada di halaman terpisah. QR yang sudah dicetak tetap bisa dibuka tanpa login.

## Stack

- PHP 8.3+ dan Laravel 13
- Vue 3 dengan Inertia
- Tailwind CSS 4
- SQLite bawaan, atau MySQL lewat `.env`
- QR digambar sebagai SVG di server. Unduhan PNG dibuat di browser dari SVG itu.

## Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
npm install
composer run dev
```

Buka http://127.0.0.1:8000. `composer run dev` menjalankan server PHP dan Vite sekaligus.

Akun admin dibuat oleh `database/seeders/AdminUserSeeder.php`. Masuk lewat http://127.0.0.1:8000/masuk.

Untuk build frontend tanpa Vite dev server:

```bash
npm run build
php artisan serve
```

## Halaman

| Alamat | Fungsi |
| --- | --- |
| `/masuk` | Login |
| `/` | Daftar proyek |
| `/buat` | Buat QR |
| `/kelola/{kode}` | Ubah QR yang sudah disimpan |
| `/q/{kode}` | Halaman publik saat QR dipindai |

## QR cerdas

QR biasa menyimpan isi langsung, misalnya Wi-Fi, vCard, telepon, atau SMS. QR cerdas menyimpan tautan pendek, jadi tujuan atau isi halaman bisa diganti tanpa mencetak ulang. Jumlah pindaian tercatat di kartu proyek.

## Tes

```bash
php artisan test
```
