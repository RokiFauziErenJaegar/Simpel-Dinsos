<?php

namespace App\Services;

use App\Enums\ServiceLocation;
use App\Models\SatisfactionSurvey;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Statistik & laporan Survei Kepuasan Masyarakat (SKM) — Permenpan RB 14/2017.
 * Dipakai bersama oleh halaman statistik publik dan export PDF petugas (fitur 4).
 */
class SkmReportGenerator
{
    /** 9 unsur SKM: kolom => label. */
    public const UNSUR = [
        'persyaratan' => 'Persyaratan',
        'prosedur' => 'Sistem, Mekanisme & Prosedur',
        'waktu' => 'Waktu Penyelesaian',
        'biaya' => 'Biaya / Tarif',
        'produk' => 'Produk Layanan',
        'kompetensi' => 'Kompetensi Pelaksana',
        'perilaku' => 'Perilaku Pelaksana',
        'sarana' => 'Sarana & Prasarana',
        'penanganan_pengaduan' => 'Penanganan Pengaduan',
    ];

    /** Kategori mutu pelayanan dari nilai indeks (skala 0-100). */
    public static function category(?float $index): string
    {
        if ($index === null) {
            return '—';
        }

        return $index >= 88 ? 'SANGAT BAIK'
            : ($index >= 76 ? 'BAIK'
            : ($index >= 65 ? 'CUKUP' : 'PERLU PERBAIKAN'));
    }

    /**
     * Agregasi statistik SKM pada rentang tanggal.
     *
     * @return array{total:int,index:?float,category:string,per_unsur:array,per_location:array,latest_saran:array}
     */
    public function stats(Carbon $from, Carbon $to): array
    {
        $surveys = SatisfactionSurvey::with('application:id,location,service_type_id')
            ->whereBetween('submitted_at', [$from, $to])
            ->get();

        $total = $surveys->count();
        $index = $total ? round($surveys->avg(fn ($s) => $s->index), 2) : null;

        // Rata-rata skor per unsur (skala 0-100).
        $perUnsur = [];
        foreach (self::UNSUR as $col => $label) {
            $vals = $surveys->pluck($col)->filter(fn ($v) => $v !== null);
            $perUnsur[$col] = [
                'label' => $label,
                'score' => $vals->isNotEmpty() ? round(($vals->avg() / 5) * 100, 2) : null,
                'count' => $vals->count(),
            ];
        }

        // Sebaran per lokasi pelayanan (fitur 5).
        $perLocation = [];
        foreach (ServiceLocation::cases() as $loc) {
            $sub = $surveys->filter(fn ($s) => optional($s->application)->location?->value === $loc->value);
            $perLocation[$loc->value] = [
                'label' => $loc->label(),
                'short' => $loc->shortLabel(),
                'total' => $sub->count(),
                'index' => $sub->isNotEmpty() ? round($sub->avg(fn ($s) => $s->index), 2) : null,
            ];
        }
        // Responden dari pengajuan online yang belum ter-stempel lokasi.
        $online = $surveys->filter(fn ($s) => optional($s->application)->location === null);
        $perLocation['online'] = [
            'label' => 'Online / Belum diproses',
            'short' => 'Online',
            'total' => $online->count(),
            'index' => $online->isNotEmpty() ? round($online->avg(fn ($s) => $s->index), 2) : null,
        ];

        $latestSaran = $surveys
            ->filter(fn ($s) => filled($s->saran))
            ->sortByDesc('submitted_at')
            ->take(10)
            ->map(fn ($s) => [
                'saran' => $s->saran,
                'name' => $s->respondent_name,
                'at' => $s->submitted_at,
            ])->values()->all();

        return [
            'total' => $total,
            'index' => $index,
            'category' => self::category($index),
            'per_unsur' => $perUnsur,
            'per_location' => $perLocation,
            'latest_saran' => $latestSaran,
        ];
    }

    /** Indeks SKM bulan berjalan (dipakai widget dashboard). */
    public static function currentMonthIndex(): ?float
    {
        $surveys = SatisfactionSurvey::whereBetween('submitted_at', [now()->startOfMonth(), now()->endOfMonth()])->get();

        return $surveys->isEmpty() ? null : round($surveys->avg(fn ($s) => $s->index), 2);
    }

    /**
     * Buat PDF laporan SKM untuk rentang tanggal. Return path relatif disk public.
     */
    public function generate(Carbon $from, Carbon $to, string $label, string $signerName = 'Kepala Dinas'): string
    {
        $stats = $this->stats($from, $to);

        $pdf = Pdf::loadView('documents.skm-report', [
            'from' => $from,
            'to' => $to,
            'label' => $label,
            'stats' => $stats,
            'signer' => $signerName,
        ])->setPaper('a4');

        $slug = \Illuminate\Support\Str::slug($label);
        $filename = "laporan-skm-{$slug}.pdf";
        $path = "reports/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
