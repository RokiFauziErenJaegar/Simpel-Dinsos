<?php

namespace App\Console\Commands;

use App\Services\LaporGoIdService;
use Illuminate\Console\Command;

class LaporPollCommand extends Command
{
    protected $signature = 'lapor:poll {--minutes=60 : Tarik aduan sejak N menit lalu}';

    protected $description = 'Polling SP4N Lapor.go.id untuk aduan baru ke instansi Dinas Sosial Pringsewu';

    public function handle(LaporGoIdService $service): int
    {
        $minutes = (int) $this->option('minutes');
        $this->info("Polling SP4N Lapor.go.id (sejak {$minutes} menit lalu)…");

        $count = $service->pollNew($minutes);

        if ($count > 0) {
            $this->info("✓ Sinkron {$count} aduan baru dari Lapor.go.id.");
        } else {
            $this->line('  Tidak ada aduan baru.');
        }

        return self::SUCCESS;
    }
}
