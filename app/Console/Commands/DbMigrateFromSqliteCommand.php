<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Salin seluruh data dari koneksi `sqlite_legacy` (file SQLite lama)
 * ke koneksi default (MySQL/MariaDB).
 *
 * Aman dijalankan ulang: pakai --fresh untuk truncate tabel tujuan dulu.
 * Cache/session/migrations dilewati otomatis.
 */
class DbMigrateFromSqliteCommand extends Command
{
    protected $signature = 'db:migrate-from-sqlite {--fresh : Truncate tabel tujuan sebelum copy}';

    protected $description = 'Salin data dari koneksi sqlite_legacy ke koneksi default (MySQL/PostgreSQL).';

    /** Tabel yang tidak perlu di-copy (akan di-isi ulang oleh aplikasi sendiri). */
    protected array $skipTables = [
        'migrations',
        'cache', 'cache_locks',
        'sessions',
        'jobs', 'job_batches', 'failed_jobs',
        'password_reset_tokens',
    ];

    /** Urutan dependency — parent dulu, baru child (untuk hormati FK). */
    protected array $order = [
        'kecamatans',
        'pekons',
        'tenants',
        'users',
        'service_types',
        'ppks_profiles',
        'applications',
        'application_documents',
        'application_logs',
        'queue_tickets',
        'output_documents',
        'complaints',
        'otp_codes',
        'satisfaction_surveys',
        'lks',
        'ugb_pub_permits',
        'data_access_logs',
        'push_subscriptions',
        'personal_access_tokens',
    ];

    public function handle(): int
    {
        $src = 'sqlite_legacy';
        $dst = config('database.default');

        if ($dst === 'sqlite') {
            $this->error('DB_CONNECTION masih sqlite. Ubah .env ke mysql dulu, lalu jalankan ulang.');

            return self::FAILURE;
        }

        $this->info("Sumber: {$src} → Tujuan: {$dst}");
        $this->newLine();

        // Validasi: tabel tujuan harus ada
        $srcTables = collect(DB::connection($src)->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
        ))->pluck('name')->all();

        $missing = collect($this->order)->filter(fn ($t) => ! Schema::connection($dst)->hasTable($t));
        if ($missing->isNotEmpty()) {
            $this->error('Tabel berikut belum ada di DB tujuan — jalankan `php artisan migrate` dulu:');
            $missing->each(fn ($t) => $this->line("  - {$t}"));

            return self::FAILURE;
        }

        $fresh = $this->option('fresh');
        if ($fresh) {
            $this->warn('Mode --fresh aktif: tabel tujuan akan di-truncate.');
            if (! $this->confirm('Lanjutkan?', true)) {
                return self::FAILURE;
            }
        }

        // Matikan FK checks di MySQL agar urutan tidak strict
        if ($this->isMysql($dst)) {
            DB::connection($dst)->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $totalCopied = 0;

        foreach ($this->order as $table) {
            if (! in_array($table, $srcTables)) {
                $this->line("  [skip] {$table} (tidak ada di sumber)");

                continue;
            }

            $srcCount = DB::connection($src)->table($table)->count();
            if ($srcCount === 0) {
                $this->line("  [kosong] {$table}");

                continue;
            }

            if ($fresh) {
                DB::connection($dst)->table($table)->truncate();
            }

            // Copy chunked agar tidak makan memori
            $copied = 0;
            DB::connection($src)->table($table)->orderBy('id')->chunk(500, function ($rows) use ($table, $dst, &$copied) {
                $data = $rows->map(fn ($r) => (array) $r)->all();
                // Normalisasi: SQLite kembalikan 1/0 untuk boolean, MySQL terima itu juga, OK.
                // JSON columns: SQLite simpan as text, MySQL accept text → OK.
                DB::connection($dst)->table($table)->insert($data);
                $copied += count($data);
            });

            $totalCopied += $copied;
            $this->line(sprintf('  [ok]   %-30s %d rows', $table, $copied));

            // Sync auto_increment counter setelah insert manual
            if ($this->isMysql($dst)) {
                $maxId = DB::connection($dst)->table($table)->max('id');
                if ($maxId) {
                    DB::connection($dst)->statement("ALTER TABLE `{$table}` AUTO_INCREMENT = ".($maxId + 1));
                }
            }
        }

        if ($this->isMysql($dst)) {
            DB::connection($dst)->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->info("Selesai. Total {$totalCopied} baris disalin.");
        $this->newLine();
        $this->line('Tips: verifikasi via `php artisan tinker` → cek User::count(), Application::count(), dsb.');

        return self::SUCCESS;
    }

    protected function isMysql(string $connection): bool
    {
        return in_array(config("database.connections.{$connection}.driver"), ['mysql', 'mariadb']);
    }
}
