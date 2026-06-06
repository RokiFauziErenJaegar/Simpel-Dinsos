<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\SatisfactionSurvey;
use Illuminate\Http\Request;

class SatisfactionSurveyController extends Controller
{
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
