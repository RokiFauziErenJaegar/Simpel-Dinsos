<?php

namespace App\Console\Commands;

use App\Models\ApplicationDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Migrasi berkas sensitif (KTP/KK/foto PPKS) dari disk 'public'
 * ke disk 'secure' (default local terisolasi, dapat di-switch ke MinIO).
 *
 * Safe untuk dijalankan ulang: hanya pindahkan berkas yang masih di public.
 */
class StorageMigrateSensitiveCommand extends Command
{
    protected $signature = 'storage:migrate-sensitive
                            {--dry-run : Tampilkan rencana migrasi tanpa eksekusi}
                            {--force : Lewati konfirmasi}';

    protected $description = 'Pindahkan berkas sensitif dari disk public ke disk secure';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if (! $dry && ! $this->option('force') && ! $this->confirm('Migrasi semua berkas sensitif ke disk secure?')) {
            return self::FAILURE;
        }

        $this->info($dry ? '[DRY-RUN] Rencana migrasi:' : 'Migrasi berjalan…');

        $public = Storage::disk('public');
        $secure = Storage::disk('secure');

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        ApplicationDocument::chunk(100, function ($docs) use ($public, $secure, $dry, &$migrated, &$skipped, &$errors) {
            foreach ($docs as $doc) {
                $path = $doc->file_path;
                if (! $path) { $skipped++; continue; }

                // Sudah di secure?
                if ($secure->exists($path)) { $skipped++; continue; }

                if (! $public->exists($path)) {
                    $this->warn("  ! tidak ditemukan di public: {$path}");
                    $errors++;
                    continue;
                }

                $contents = $public->get($path);
                $this->line("  → {$path} (".number_format(strlen($contents))." bytes)");

                if (! $dry) {
                    $secure->put($path, $contents);
                    $public->delete($path);
                }
                $migrated++;
            }
        });

        $this->newLine();
        $this->table(['Status', 'Jumlah'], [
            ['Pindah', $migrated],
            ['Skip (sudah ada / tanpa path)', $skipped],
            ['Error (tidak ditemukan)', $errors],
        ]);

        if (! $dry) {
            $this->info('Selesai. Untuk mengaktifkan MinIO di prod, set SECURE_DISK_DRIVER=minio + MINIO_* di .env.');
        }

        return self::SUCCESS;
    }
}
