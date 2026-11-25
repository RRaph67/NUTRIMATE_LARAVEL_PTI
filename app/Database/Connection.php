<?php

namespace App\Database;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Satu pintu akses database untuk seluruh aplikasi.
 *
 * NutriMate tetap memakai **query PDO mentah** (prepared statement), bukan
 * Eloquent/query builder. Kelas ini hanya memastikan seluruh repository
 * berbagi satu instance PDO yang sama: koneksinya dikelola Laravel lewat
 * config/database.php (`DB_CONNECTION`, `DB_*` di .env), lalu PDO-nya
 * dipakai langsung untuk semua query.
 */
final class Connection
{
    private static ?PDO $pdo = null;

    /**
     * Ambil instance PDO milik koneksi default Laravel.
     *
     * Mode fetch diset ke FETCH_ASSOC supaya seluruh query mengembalikan
     * array ber-key nama kolom saja (tanpa duplikat key numerik).
     */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return self::$pdo = $pdo;
    }

    /**
     * Jalankan beberapa query dalam satu transaksi.
     *
     * Bila pemanggil sudah berada di dalam transaksi (mis. pengujian dengan
     * RefreshDatabase), transaksi luar yang dipakai agar tidak terjadi
     * "active transaction" bersarang yang ditolak PDO.
     *
     * @template T
     *
     * @param  callable(PDO): T  $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();

        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }

        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }
    }

    /**
     * Lepas referensi PDO (dipakai pengujian setelah koneksi di-reset).
     */
    public static function forget(): void
    {
        self::$pdo = null;
    }
}
