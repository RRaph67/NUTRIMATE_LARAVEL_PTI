# NutriMate — AI Project Brief (Landasan Brief untuk AI)

Dokumen ini adalah landasan brief untuk AI/agen coding yang bekerja di repositori ini.
Baca sebelum melakukan perubahan.

## 1. Ringkasan Proyek
- Nama: **NutriMate** — katalog makanan bernutrisi & ramah kantong mahasiswa.
- Tipe: Web app server-rendered (Blade).
- Stack: **Laravel 12**, PHP 8.2+, MariaDB/MySQL via **query PDO mentah**, CSS/JS vanilla.
- Bahasa kode: PHP 8+, komentar/pesan validasi/teks UI dalam Bahasa Indonesia.
- Entry point: `public/index.php` (front controller Laravel).
- Riwayat: proyek ini hasil konversi dari Native PHP MVC; versi lama diarsipkan di
  `../_backup_native_php/`.

## 2. Struktur Folder (aktual)
```
app/
├── Auth/
│   ├── PdoUser.php             # Implementasi Authenticatable tanpa Eloquent
│   └── PdoUserProvider.php     # UserProvider berbasis PDO (driver auth "pdo")
├── Database/Connection.php     # Singleton PDO + helper transaksi
├── Http/
│   ├── Controllers/            # HomeController, Auth/AuthController, FoodController,
│   │                           # ProfileController, MealPlanController,
│   │                           # RecommendationController, CalculatorController
│   ├── Requests/               # Auth/, Food/, Profile/, Calculator/, Concerns/
│   └── ViewComposers/LayoutComposer.php
├── Providers/AppServiceProvider.php  # Driver auth "pdo" + view composer
├── Repositories/               # UserRepository, ProfileRepository, FoodRepository,
│   │                           # MealPlanRepository, NutritionCalculationRepository
├── Services/                   # Recommender, MealPlanService, MenuBuilder
└── Support/                    # FoodCategory, FoodSort, MealSlot, Number,
                                # NutritionCalculator, Navigation, helpers.php
bootstrap/app.php               # Middleware & redirect tamu → modal login
config/                         # Konfigurasi Laravel
database/
├── migrations/                 # DDL resmi (idempoten; tidak mengubah data lama)
├── schema.sql                  # DDL referensi manual
└── seeders/                    # FoodSeeder (36 menu), DemoUserSeeder, data/foods.php
public/
├── index.php
└── assets/{css,js,img}/        # Aset statis, tanpa Vite
resources/views/                # Blade: layouts, partials, home, foods, profile,
                                # meal-planner, recommendation, calculator, errors
routes/web.php                  # Semua rute
tests/{Unit,Feature}/           # PHPUnit
```

## 3. Konvensi Arsitektur
- **Database wajib PDO mentah.** Jangan menambah Eloquent model, jangan memakai query
  builder untuk CRUD fitur. Semua SQL ada di `app/Repositories/*`.
- Akses koneksi hanya lewat `App\Database\Connection::pdo()`. Jangan membuat `new PDO()`.
  Untuk operasi multi-query pakai `Connection::transaction(callable)`.
- Query wajib prepared statement; input pengguna tidak boleh dikonkatenasi ke SQL.
  Kolom/arah `ORDER BY` hanya dari konstanta `App\Support\FoodSort`.
- Controller tipis: validasi di Form Request, query di Repository, aturan lintas-fitur
  di Service. Controller tidak menulis SQL.
- View hanya Blade di `resources/views`. Escape otomatis `{{ }}`; `{!! !!}` hanya untuk
  SVG ikon statis.
- Data navbar/sidebar disediakan `LayoutComposer` (jangan query langsung di view).
- Session kunci: `calculator.items` (susunan menu), `calculator.result` (hasil sekali tampil).

## 4. Routing (`routes/web.php`)
- Rute publik: `/` (home), `/login`, `/register` (POST), `/logout` (POST).
- Rute `auth` middleware: `foods` (resource), `profile`, `meal-planner`,
  `recommendation` + `POST /api/recommend`, `calculator`.
- Login/register berada di **modal halaman beranda**; tamu diarahkan ke
  `route('home', ['auth' => 'login'])` lewat `redirectGuestsTo` di `bootstrap/app.php`.
- **Rute destruktif wajib POST/DELETE** (method spoofing `@method`), tidak boleh GET:
  hapus makanan, kosongkan slot, hapus riwayat, bersihkan riwayat, ganti password,
  hapus item menu.
- Tambah rute baru: daftarkan di `routes/web.php` dengan nama yang deskriptif.

## 5. Database
- DB: MariaDB, host `127.0.0.1`, port `3307`, db `nutrimate_pemweb_kelas`, user `root`.
- Konfigurasi dibaca dari `.env` (`DB_*`) lewat `config/database.php`.
- `SESSION_DRIVER=file`, `CACHE_STORE=file` — DB tidak punya tabel session/cache.
- Primary key `foods` = UUID string (36). `users`/`profiles`/`meal_plans`/
  `nutrition_calculations` = auto-increment BIGINT.
- Skema resmi: `database/migrations`. `database/schema.sql` hanya referensi manual.
- Migrasi bersifat idempoten (`Schema::hasTable` → return) agar database lama tidak
  terhapus. **Jangan** `migrate:fresh` pada database yang berisi data.
- Seed makanan: `php artisan db:seed` (idempoten, `ON DUPLICATE KEY UPDATE`).

## 6. Konvensi Kode
- Indentasi 4 spasi, PSR-12, diformat dengan `./vendor/bin/pint`.
- Penamaan: CamelCase untuk kelas, camelCase untuk method, snake_case untuk kolom DB
  dan key array.
- Output HTML: Blade `{{ }}` (auto-escape). Format angka pakai `fmt_num()`/`rupiah()`.
- Pesan flash: `->with('success'|'error', $pesan)`; ditampilkan `layouts/alerts`.
- Validasi: Form Request + pesan Bahasa Indonesia di `messages()`.
- Jangan menambah dependensi npm/Vite; aset disajikan statis dari `public/assets`.
- Setelah perubahan: jalankan `php artisan test` dan `./vendor/bin/pint --test`.

## 7. Fitur & Aturan Bisnis
- **Meal Planner**: 3 slot (`sarapan`, `makan_siang`, `makan_malam`), porsi 1–2,
  gizi & harga dikalikan porsi dan disimpan denormalisasi per baris.
- **Rekomendasi**: `App\Services\Recommender` (kandidat per slot → Pareto → kombinasi
  terbaik dengan penalti pengulangan & plafon budget). Budget 10.000–1.000.000.
- **Kalkulator**: Mifflin-St Jeor, makro 15/55/30, IMT ambang Asia-Pasifik. TDEE
  otomatis menjadi `profiles.calorie_target` (budget tidak diubah).
- **Menu Builder**: session `food_id => porsi`, porsi di-*clamp* 1–2, makanan yang
  sudah dihapus dari DB dibuang otomatis.
- **Auth**: password bcrypt (`Hash::make`/`Hash::check`); `Auth::attempt` lewat
  `PdoUserProvider`; session diregenerasi setelah login/register.

## 8. Yang Harus Dihindari
- Jangan membuat model Eloquent atau memakai `DB::table()` untuk CRUD fitur.
- Jangan mengubah nama tabel/kolom (data lama harus tetap terbaca).
- Jangan menghapus fitur yang sudah ada tanpa diminta.
- Jangan menaruh logika query di view atau controller.
- Jangan mengembalikan HTML penuh dari endpoint JSON.
