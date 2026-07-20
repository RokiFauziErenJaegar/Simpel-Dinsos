<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\SatisfactionSurvey;
use App\Services\SkmReportGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SatisfactionSurveyController extends Controller
{
    /**
     * Halaman statistik SKM publik (fitur 4) — total responden + indeks +
     * sebaran per unsur. Rentang default: bulan berjalan; bisa custom via query.
     */
    public function publicStats(Request $request, SkmReportGenerator $generator)
    {
        [$from, $to, $label, $range] = $this->resolveRange($request);

        $stats = $generator->stats($from, $to);
        $allTime = SatisfactionSurvey::count();

        return view('public.skm.statistik', [
            'stats' => $stats,
            'allTime' => $allTime,
            'from' => $from,
            'to' => $to,
            'label' => $label,
            'range' => $range,
        ]);
    }

    /**
     * Tentukan rentang tanggal dari query: ?range=bulan|kustom.
     * Untuk kustom: ?from=YYYY-MM-DD&to=YYYY-MM-DD. Default bulan berjalan.
     *
     * @return array{0:Carbon,1:Carbon,2:string,3:string}
     */
    protected function resolveRange(Request $request): array
    {
        $range = $request->query('range', 'bulan');

        if ($range === 'kustom' && $request->filled('from') && $request->filled('to')) {
            try {
                $from = Carbon::parse($request->query('from'))->startOfDay();
                $to = Carbon::parse($request->query('to'))->endOfDay();
                if ($to->lt($from)) {
                    [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                }

                return [$from, $to, $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y'), 'kustom'];
            } catch (\Throwable $e) {
                // fallthrough ke default
            }
        }

        if ($range === 'tahun') {
            $from = now()->startOfYear();
            $to = now()->endOfYear();

            return [$from, $to, 'Tahun '.now()->year, 'tahun'];
        }

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        return [$from, $to, now()->translatedFormat('F Y'), 'bulan'];
    }

    public function create(string $code)
    {
        $application = Application::with('serviceType')->where('code', $code)->firstOrFail();

        if ($application->status?->value !== 'completed') {
            abort(403, 'Survei hanya tersedia untuk pengajuan yang sudah selesai.');
        }

        if (SatisfactionSurvey::where('application_id', $application->id)->exists()) {
            return view('public.skm.already', compact('application'));
        }

        return view('public.skm.create', compact('application'));
    }

    public function store(Request $request, string $code)
    {
        $application = Application::where('code', $code)->firstOrFail();

        $rules = [
            'saran' => 'nullable|string|max:2000',
            'respondent_name' => 'nullable|string|max:150',
            'respondent_contact' => 'nullable|string|max:100',
        ];
        foreach (['persyaratan', 'prosedur', 'waktu', 'biaya', 'produk', 'kompetensi', 'perilaku', 'sarana', 'penanganan_pengaduan'] as $u) {
            $rules[$u] = 'required|integer|min:1|max:5';
        }

        $data = $request->validate($rules);

        if (SatisfactionSurvey::where('application_id', $application->id)->exists()) {
            return redirect()->route('home');
        }

        SatisfactionSurvey::create(array_merge($data, [
            'application_id' => $application->id,
            'submitted_at' => now(),
        ]));

        // SKM selesai → kalau surat sudah terbit, langsung antar ke unduhan.
        $application->load('outputDocument');
        if ($application->outputDocument) {
            return redirect()->route('document.download', ['token' => $application->outputDocument->verification_token]);
        }

        return view('public.skm.thanks', compact('application'));
    }
}
