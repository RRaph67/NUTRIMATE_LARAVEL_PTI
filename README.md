# NutriMate — Laravel + PDO

Aplikasi katalog makanan bernutrisi dan ramah kantong mahasiswa. Proyek ini adalah
**konversi dari Native PHP MVC menjadi Laravel 12**, dengan satu aturan penting:
**lapisan database tetap memakai query PDO mentah** (prepared statement), bukan
Eloquent dan bukan migration-driven schema.

---

## 📁 Struktur Proyek

```text
NUTRIMATE_13_PEMWEB/
├── app/
│   ├── Auth/                    # PdoUser & PdoUserProvider (guard Laravel + PDO)
│   ├── Database/Connection.php  # Satu instance PDO untuk seluruh aplikasi
│   ├── Http/
│   │   ├── Controllers/         # Home, Auth, Food, Profile, MealPlan, Recommendation, Calculator
│   │   ├── Requests/            # Validasi form terpusat (Auth, Food, Profile, Calculator)
│   │   └── ViewComposers/       # Data navbar & sidebar
│   ├── Providers/               # Registrasi driver auth "pdo" + view composer
│   ├── Repositories/            # Semua query PDO (users, profiles, foods, meal_plans, riwayat)
│   ├── Services/                # Recommender, MealPlanService, MenuBuilder
│   └── Support/                 # FoodCategory, FoodSort, MealSlot, Number, NutritionCalculator, helper
├── bootstrap/app.php            # Konfigurasi middleware & redirect tamu
├── config/                      # Konfigurasi Laravel (app, auth, database, session, …)
├── database/
│   ├── migrations/              # DDL resmi (dijalankan sekali; tidak memakai Eloquent)
│   ├── schema.sql               # Referensi DDL manual (impor cepat tanpa artisan)
│   └── seeders/                 # FoodSeeder (36 menu), DemoUserSeeder (akun demo)
├── public/
│   ├── index.php                # Front controller Laravel
│   └── assets/                  # CSS, JS, gambar (dipakai apa adanya dari versi native)
│       ├── css/app.css
│       ├── js/app.js
│       └── js/recommendation.js
├── resources/views/             # Blade: layouts, partials, home, foods, profile, meal-planner, …
├── routes/web.php               # Seluruh rute aplikasi
├── tests/                       # Unit + Feature test (PHPUnit)
├── .env.example                 # Template konfigurasi
├── AGENTS.md                    # Brief untuk AI/agen coding
└── README.md
```

---

## ⚙️ Cara Menjalankan

### 1. Prasyarat
- PHP 8.2+ (diuji pada PHP 8.5) dengan ekstensi `pdo_mysql`
- Composer 2
- MariaDB / MySQL

### 2. Pasang dependensi & konfigurasi
```bash
composer install
cp .env.example .env      # Windows: copy .env.example .env
php artisan key:generate
```
Sesuaikan koneksi database di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=nutrimate_pemweb_kelas
DB_USERNAME=root
DB_PASSWORD=root123
```
> `SESSION_DRIVER` dan `CACHE_STORE` disetel `file` karena database NutriMate tidak
> memiliki tabel `sessions`/`cache`.

### 3. Siapkan database
Pilih salah satu cara:

**a. Database sudah ada (kondisi normal saat konversi)**
```bash
php artisan migrate      # aman: tabel yang sudah ada dilewati
php artisan db:seed      # mengisi 36 menu awal (idempoten)
```

**b. Database baru dari nol**
```bash
php artisan migrate
php artisan db:seed
```
Atau tanpa artisan sama sekali:
```bash
mysql -u root -p < database/schema.sql
php artisan db:seed
```

### 4. Jalankan
```bash
php artisan serve
# atau
php -S localhost:8000 -t public
```
Buka `http://localhost:8000`.

### Akun demo
```bash
php artisan db:seed --class=DemoUserSeeder
```
- **Email**: `test@example.com`
- **Password**: `password`

---

## 🧮 Fitur Utama

| Fitur | Rute | Keterangan |
|---|---|---|
| Home | `/` | Landing page, modal login/daftar, popup kalkulator |
| Food Database | `/foods` | Katalog 36 menu: pencarian, filter kategori, pengurutan, modal detail |
| Food CRUD | `/foods/create`, `/foods/{id}/edit` | Tambah, ubah, hapus menu (validasi terpusat) |
| Meal Planner | `/meal-planner` | Rencana makan harian 3 slot: Sarapan, Makan Siang, Makan Malam |
| Recommendation | `/recommendation` | Paket menu otomatis sesuai budget & target kalori profil |
| API Rekomendasi | `POST /api/recommend` | Endpoint JSON (CSRF-protected) untuk halaman rekomendasi |
| Nutrition Calculator | `/calculator` | BMR, TDEE, IMT, makronutrisi, penyusun menu, dan riwayat |
| Profil | `/profile` | Data diri, target kalori, budget harian, ganti password |

### Nutrition Calculator
- Rumus **Mifflin-St Jeor** untuk BMR, dikali faktor aktivitas menjadi **TDEE**.
- Pembagian makronutrisi: **protein 15%**, **karbohidrat 55%**, **lemak 30%**.
- Kategori **IMT** memakai ambang batas Asia-Pasifik.
- Setiap perhitungan disimpan sebagai riwayat (hapus satu per satu atau seluruhnya).
- **Sinkronisasi otomatis**: TDEE langsung menjadi `calorie_target` di profil, sehingga
  Meal Planner dan halaman Recommendation memakai target kalori yang sama.
  `daily_budget` milik pengguna tidak diubah.
- Tersedia **popup kalkulator cepat** di navbar yang menghitung di sisi klien tanpa
  menyimpan; tombol "Hitung & Simpan" menyimpannya ke riwayat.

#### Penyusun Menu (Menu Builder)
1. Pilih bahan makanan dari Food Database beserta **porsi 1–2** (sama dengan batas Meal Planner).
2. Sistem menghitung total **kalori, protein, karbohidrat, lemak, dan harga** (`nilai gizi × porsi`).
3. Total menu dibandingkan dengan target kalori (TDEE): persentase, progress bar, dan sisa kebutuhan.
4. Pilih slot makan lalu **Simpan ke Meal Planner**; setiap bahan menjadi baris tersendiri.

Catatan teknis:
- Pilihan menu disimpan di **session** (`calculator.items`, format `food_id => porsi`).
- Satu makanan tidak bisa masuk dua kali; menambah ulang memperbarui porsinya.
- Makanan yang sudah dihapus dari database otomatis dibuang dari session.
- Porsi di luar rentang di-*clamp* ke 1–2 dan slot di luar 3 slot sah ditolak.

### Mesin Rekomendasi
- Kandidat menu dibangun per slot: 1 karbohidrat + 1–2 protein + pelengkap (sayur/buah opsional).
- Kandidat dikompresi dengan prinsip **Pareto** (harga vs skor gizi), lalu kombinasi terbaik
  dipilih dengan penalti pengulangan menu, plafon budget per slot, dan dorongan pemenuhan target kalori.
- Preferensi pengguna: `hemat`, `protein`, `sayur`, `buah`, `seimbang`.
- Hasil berisi rincian menu per slot, total harga, sisa budget, gizi total, dan `payload`
  siap dikirim ke Meal Planner.

---

## 🏗️ Keputusan Arsitektur

### Kenapa tetap PDO?
Aplikasi ini adalah tugas kuliah yang sejak awal memakai PDO dengan skema yang sudah
ada. Konversi ke Laravel dilakukan untuk **struktur & maintainability**, bukan untuk
mengganti cara akses data:

- **Tidak ada Eloquent model.** Tabel diakses lewat `App\Repositories\*` dengan
  `PDO::prepare()` + parameter terikat.
- **`App\Database\Connection::pdo()`** membungkus PDO milik koneksi default Laravel,
  jadi konfigurasi tetap satu pintu di `config/database.php` + `.env`.
- **Autentikasi** memakai guard/session Laravel dengan **provider kustom**
  (`App\Auth\PdoUserProvider`, driver `pdo` di `config/auth.php`). Laravel menangani
  sesi, regenerasi, dan "remember me"; query user tetap PDO.
- **Validasi** dipindah ke Form Request (`app/Http/Requests`) agar controller tipis.
- **Migration** disediakan sebagai DDL resmi (`database/migrations`) tetapi **tidak
  dipakai untuk mengubah data**; setiap migrasi melewati tabel yang sudah ada
  (`Schema::hasTable`) sehingga database lama tetap aman.
- **Rute destruktif** (hapus makanan, kosongkan slot, hapus riwayat, ganti password)
  hanya menerima `POST` + `_method` (Laravel method spoofing), bukan `GET`.

### Alur request
```text
routes/web.php → Controller → FormRequest (validasi) → Repository (PDO) → Blade view
```

### Konvensi
- Bahasa komentar, pesan validasi, dan teks UI: **Bahasa Indonesia**.
- Format angka & rupiah lewat helper global `fmt_num()` dan `rupiah()`
  (`app/Support/helpers.php`, terdaftar di `composer.json` → `autoload.files`).
- Data bersama navbar/sidebar disediakan `App\Http\ViewComposers\LayoutComposer`.
- CSS/JS **tidak** memakai Vite; aset statis disajikan dari `public/assets/`.

---

## 🧪 Pengujian

```bash
php artisan test
```
Pengujian memakai database MariaDB terpisah `nutrimate_test` (lihat `phpunit.xml`)
karena query aplikasi memakai sintaks MySQL. Buat dulu database-nya:
```sql
CREATE DATABASE nutrimate_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Cakupan: unit (format angka, rumus gizi, IMT, pengurutan, slot) dan feature
(autentikasi, CRUD makanan, Meal Planner, rekomendasi + API JSON, kalkulator +
menu builder, profil, smoke test halaman).

Format kode:
```bash
./vendor/bin/pint          # rapikan otomatis
./vendor/bin/pint --test   # cek saja
```

---

## 📝 Catatan Migrasi dari Versi Native

- Versi native lama (beserta riwayat git-nya) diarsipkan di `../_backup_native_php/`.
- Nama & isi tabel database **tidak berubah**, sehingga data lama tetap terbaca.
- Semua URL berubah dari `index.php?page=...` menjadi rute Laravel yang bersih
  (mis. `?page=foods` → `/foods`), kecuali nama route internal.
- Helper lama (`url()`, `flash()`, `old()`, `auth_user()`) dipetakan ke padanan
  Laravel (`route()`, `session()->with()`, `old()`, `Auth::user()`).
