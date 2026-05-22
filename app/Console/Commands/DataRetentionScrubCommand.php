<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DataAccessLog;
use App\Models\OtpCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Implementasi retensi UU PDP Pasal 38.
 *
 * Aturan default (dapat dioverride via config/retention.php nanti):
 *   - Foto PPKS:           1 tahun setelah pengajuan selesai
 *   - Berkas KTP/KK upload: 3 tahun setelah pengajuan selesai
 *   - Application logs:    10 tahun (anonymize NIK)
 *   - OTP codes:           langsung hapus setelah expire
 *   - Data access logs:    2 tahun
 *   - Soft-deleted records: force delete setelah 30 hari
 */
class DataRetentionScrubCommand extends Command
{
    protected $signature = 'pdp:scrub
                            {--dry-run : Tampilkan apa yang akan dihapus tanpa benar-benar menghapus}
                            {--force : Lewati konfirmasi}';

    protected $description = 'Bersihkan data sensitif sesuai retensi UU PDP (Pasal 38)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if (! $dry && ! $this->option('force') && ! $this->confirm('Yakin jalankan scrub data sensitif?')) {
            $this->warn('Dibatalkan.');
            return self::FAILURE;
        }

        $this->info($dry ? '[DRY-RUN] Menampilkan rencana scrub:' : 'Menjalankan scrub data sensitif…');

        $stats = [
            'docs_scrubbed' => $this->scrubApplicationDocuments($dry),
            'force_deleted' => $this->forceDeleteSoftDeleted($dry),
            'otp_expired' => $this->purgeExpiredOtp($dry),
            'access_logs_purged' => $this->purgeOldAccessLogs($dry),
        ];

        $this->newLine();
        $this->table(['Kategori', 'Jumlah'], collect($stats)->map(fn ($v, $k) => [$k, $v])->all());

        return self::SUCCESS;
    }

    protected function scrubApplicationDocuments(bool $dry): int
    {
        // Hapus berkas pengajuan yang sudah selesai > 3 tahun
        $cutoff = now()->subYears(3);
        $docs = ApplicationDocument::whereHas('application', function ($q) use ($cutoff) {
            $q->where('status', 'completed')->where('completed_at', '<', $cutoff);
        })->get();

        $count = 0;
        foreach ($docs as $doc) {
            $this->line("  - Berkas: {$doc->original_name} (app #{$doc->application_id})");
            if (! $dry) {
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
                $doc->delete(); // soft-delete jika trait aktif
            }
            $count++;
        }
        return $count;
    }

    protected function forceDeleteSoftDeleted(bool $dry): int
    {
        // Force-delete record yang sudah soft-deleted lebih dari 30 hari
        $cutoff = now()->subDays(30);
        $count = 0;

        foreach ([Application::class, ApplicationDocument::class] as $modelClass) {
            // Cek apakah model punya soft deletes
            $model = new $modelClass;
            if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass))) {
                continue;
            }
            $stale = $modelClass::onlyTrashed()->where('deleted_at', '<', $cutoff)->get();
            foreach ($stale as $record) {
                $this->line("  - Force delete {$modelClass} #{$record->id}");
                if (! $dry) {
                    $record->forceDelete();
                }
                $count++;
            }
        }
        return $count;
    }

    protected function purgeExpiredOtp(bool $dry): int
    {
        $query = OtpCode::where('expires_at', '<', now()->subMinutes(10));
        $count = $query->count();
        if (! $dry && $count > 0) {
            $query->delete();
        }
        if ($count) $this->line("  - OTP expired: {$count} rows");
        return $count;
    }

    protected function purgeOldAccessLogs(bool $dry): int
    {
        $query = DataAccessLog::where('created_at', '<', now()->subYears(2));
        $count = $query->count();
        if (! $dry && $count > 0) {
            $query->delete();
        }
        if ($count) $this->line("  - Data access logs > 2 tahun: {$count} rows");
        return $count;
    }
}
