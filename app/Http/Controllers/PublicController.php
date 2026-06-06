<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\OutputDocument;
use App\Models\QueueTicket;
use App\Models\SatisfactionSurvey;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    public function home()
    {
        $featuredServices = ServiceType::active()->featured()->orderBy('order_no')->get();
        $allCount = ServiceType::active()->count();

        $today = today();
        $stats = [
            'served_today' => QueueTicket::whereDate('ticket_date', $today)->where('status', 'done')->count(),
            'waiting' => QueueTicket::whereDate('ticket_date', $today)->where('status', 'waiting')->count(),
            'completed_month' => Application::where('status', 'completed')->whereMonth('completed_at', now()->month)->count(),
        ];

        $nowServing = QueueTicket::whereDate('ticket_date', $today)
            ->where('status', 'serving')
            ->orderBy('called_at', 'desc')
            ->take(2)->get();

        $upcoming = QueueTicket::whereDate('ticket_date', $today)
            ->where('status', 'waiting')
            ->orderBy('id')
            ->take(5)->get();

        return view('public.home', compact('featuredServices', 'allCount', 'stats', 'nowServing', 'upcoming'));
    }

    public function services(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $bidang = $request->input('bidang');

        $services = ServiceType::active()
            ->when($q, fn ($query) => $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            }))
            ->when($bidang, fn ($query) => $query->where('bidang', $bidang))
            ->orderBy('order_no')
            ->get();

        $bidangs = ServiceType::active()->select('bidang')->distinct()->pluck('bidang');

        return view('public.services.index', compact('services', 'q', 'bidang', 'bidangs'));
    }

    public function serviceShow(string $slug)
    {
        $service = ServiceType::active()->where('slug', $slug)->firstOrFail();
        return view('public.services.show', compact('service'));
    }

    public function checkStatusIndex(Request $request)
    {
        $code = trim((string) $request->input('code'));
        $application = null;
        if ($code) {
            $application = Application::with(['serviceType', 'logs.user', 'queueTicket', 'documents', 'outputDocument'])
                ->where('code', $code)->first();
        }
        return view('public.check-status', compact('code', 'application'));
    }

    public function complaintCreate()
    {
        return view('public.complaint');
    }

    public function complaintStore(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:200',
            'content' => 'required|string|max:5000',
            'reporter_name' => 'nullable|string|max:150',
            'reporter_contact' => 'nullable|string|max:100',
            'is_anonymous' => 'sometimes|boolean',
        ]);

        $complaint = \App\Models\Complaint::create([
            'code' => \App\Models\Complaint::generateCode(),
            'channel' => 'web',
            'subject' => $data['subject'],
            'content' => $data['content'],
            'reporter_name' => $data['is_anonymous'] ?? false ? null : ($data['reporter_name'] ?? null),
            'reporter_contact' => $data['is_anonymous'] ?? false ? null : ($data['reporter_contact'] ?? null),
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'status' => 'open',
        ]);

        return redirect()->route('pengaduan.create')
            ->with('success', 'Pengaduan Anda telah terkirim dengan kode '.$complaint->code.'. Terima kasih.');
    }

    public function verifyDocument(string $token)
    {
        $document = OutputDocument::with(['application.serviceType', 'signedBy'])
            ->where('verification_token', $token)
            ->firstOrFail();

        return view('public.verify-document', compact('document'));
    }

    /**
     * Unduh PDF surat hasil. Pemohon WAJIB mengisi SKM lebih dulu;
     * jika belum, dialihkan ke form survei. Setelah SKM terisi, surat
     * langsung dapat diunduh (lihat SatisfactionSurveyController::store).
     */
    public function downloadDocument(string $token)
    {
        $document = OutputDocument::with('application')
            ->where('verification_token', $token)
            ->firstOrFail();

        $application = $document->application;

        if (! SatisfactionSurvey::where('application_id', $application->id)->exists()) {
            return redirect()->route('skm.create', ['code' => $application->code])
                ->with('skm_required', 'Mohon isi Survei Kepuasan Masyarakat (SKM) di bawah ini terlebih dahulu. Setelah selesai, surat Anda otomatis dapat diunduh.');
        }

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Berkas surat tidak ditemukan.');
        }

        return Storage::disk('public')->response(
            $document->file_path,
            'surat-'.$application->code.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
