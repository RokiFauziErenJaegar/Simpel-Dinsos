<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Complaint;
use App\Models\SatisfactionSurvey;
use App\Models\ServiceType;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Generator laporan bulanan untuk Bupati — PDF ringkas berisi:
 * KPI utama, performa SLA per layanan, sebaran kecamatan, anggaran (placeholder),
 * dan ringkasan narasi.
 */
class MonthlyReportGenerator
{
    public function generate(Carbon $month, string $signerName = 'Kepala Dinas'): string
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $total = Application::whereBetween('submitted_at', [$from, $to])->count();
        $completed = Application::whereBetween('submitted_at', [$from, $to])->where('status', 'completed')->count();
        $rejected = Application::whereBetween('submitted_at', [$from, $to])->where('status', 'rejected')->count();
        $onTime = Application::whereBetween('submitted_at', [$from, $to])
            ->where('status', 'completed')
            ->whereColumn('completed_at', '<=', 'sla_due_at')
            ->count();
        $onTimePct = $completed > 0 ? round(($onTime / $completed) * 100, 1) : 0;

        $surveys = SatisfactionSurvey::whereBetween('submitted_at', [$from, $to])->get();
        $skmIndex = $surveys->isEmpty() ? null : round($surveys->avg(fn ($s) => $s->index), 2);

        $complaints = Complaint::whereBetween('created_at', [$from, $to])->count();
        $resolvedComplaints = Complaint::whereBetween('created_at', [$from, $to])->whereNotNull('resolved_at')->count();

        // Performa per layanan
        $perService = ServiceType::active()
            ->orderBy('order_no')
            ->get()
            ->map(function ($s) use ($from, $to) {
                $apps = Application::where('service_type_id', $s->id)
                    ->whereBetween('submitted_at', [$from, $to])
                    ->get();
                $cnt = $apps->count();
                $done = $apps->where('status.value', 'completed')->count();
                $ontime = $apps->filter(fn ($a) => $a->status?->value === 'completed' && $a->completed_at && $a->sla_due_at && $a->completed_at->lte($a->sla_due_at))->count();
                $pct = $done > 0 ? round(($ontime / $done) * 100) : null;

                return [
                    'code' => $s->code,
                    'name' => $s->name,
                    'total' => $cnt,
                    'completed' => $done,
                    'sla_pct' => $pct,
                ];
            })
            ->filter(fn ($r) => $r['total'] > 0)
            ->values();

        $pdf = Pdf::loadView('documents.monthly-report', [
            'month' => $month,
            'from' => $from, 'to' => $to,
            'total' => $total, 'completed' => $completed, 'rejected' => $rejected,
            'on_time' => $onTime, 'on_time_pct' => $onTimePct,
            'skm_index' => $skmIndex, 'skm_count' => $surveys->count(),
            'complaints' => $complaints, 'complaints_resolved' => $resolvedComplaints,
            'per_service' => $perService,
            'signer' => $signerName,
            'narrative' => $this->buildNarrative($total, $completed, $onTimePct, $skmIndex, $month),
        ])->setPaper('a4');

        $filename = sprintf('laporan-bulanan-%s.pdf', $month->format('Y-m'));
        $path = "reports/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    protected function buildNarrative(int $total, int $completed, float $pct, ?float $skm, Carbon $month): string
    {
        $monthName = $month->translatedFormat('F Y');
        $parts = ["Selama bulan {$monthName}, Dinas Sosial Kabupaten Pringsewu menerima sebanyak {$total} pengajuan layanan, dengan {$completed} di antaranya telah selesai diproses."];

        if ($pct >= 90) {
            $parts[] = "Tingkat ketepatan SLA mencapai {$pct}%, melampaui target nasional 90%. Hal ini menunjukkan konsistensi kinerja tim dalam pelayanan publik.";
        } elseif ($pct > 0) {
            $parts[] = "Tingkat ketepatan SLA berada di {$pct}%, di bawah target 90%. Tim akan meningkatkan koordinasi internal dan kapasitas verifikasi lapangan untuk perbaikan bulan berikutnya.";
        }

        if ($skm !== null) {
            $kategori = $skm >= 88 ? 'SANGAT BAIK' : ($skm >= 76 ? 'BAIK' : ($skm >= 65 ? 'CUKUP' : 'PERLU PERBAIKAN'));
            $parts[] = "Indeks Kepuasan Masyarakat tercatat {$skm}/100 (kategori {$kategori}) berdasarkan survei pasca-layanan otomatis sesuai Permenpan RB 14/2017.";
        }

        $parts[] = 'Layanan terus dijalankan secara digital end-to-end, dari pendaftaran online, verifikasi berkas, hingga penerbitan surat ber-QR — sejalan dengan moto Dinsos Pringsewu: Cepat, Adaptif, Responsif, dan Empati.';

        return implode("\n\n", $parts);
    }
}
