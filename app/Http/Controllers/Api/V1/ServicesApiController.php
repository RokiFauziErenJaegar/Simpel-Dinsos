<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\QueueTicket;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicesApiController extends Controller
{
    public function index(): JsonResponse
    {
        $services = ServiceType::active()
            ->orderBy('order_no')
            ->get(['id', 'code', 'slug', 'name', 'description', 'bidang', 'sla_display', 'icon', 'is_featured']);

        return response()->json([
            'data' => $services,
            'total' => $services->count(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $service = ServiceType::active()->where('slug', $slug)->firstOrFail();
        return response()->json($service);
    }

    public function queueStatus(): JsonResponse
    {
        $today = today();
        return response()->json([
            'now_serving' => QueueTicket::whereDate('ticket_date', $today)
                ->where('status', 'serving')
                ->get(['ticket_number', 'counter']),
            'waiting_count' => QueueTicket::whereDate('ticket_date', $today)
                ->where('status', 'waiting')->count(),
            'served_today' => QueueTicket::whereDate('ticket_date', $today)
                ->where('status', 'done')->count(),
        ]);
    }

    public function myApplications(Request $request): JsonResponse
    {
        $apps = $request->user()
            ->applications()
            ->with(['serviceType:id,code,name,sla_display', 'queueTicket', 'outputDocument'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $apps->map(fn ($a) => [
                'code' => $a->code,
                'service_code' => $a->serviceType->code,
                'service_name' => $a->serviceType->name,
                'beneficiary' => $a->beneficiary_name,
                'status' => $a->status?->value,
                'status_label' => $a->status?->label(),
                'submitted_at' => $a->submitted_at,
                'sla_due_at' => $a->sla_due_at,
                'completed_at' => $a->completed_at,
                'queue_ticket' => $a->queueTicket?->ticket_number,
                'verify_url' => $a->outputDocument
                    ? route('document.verify', ['token' => $a->outputDocument->verification_token])
                    : null,
            ]),
        ]);
    }

    public function applicationStatus(Request $request, string $code): JsonResponse
    {
        $app = Application::with(['serviceType', 'logs', 'queueTicket', 'outputDocument'])
            ->where('code', $code)
            ->firstOrFail();

        return response()->json([
            'code' => $app->code,
            'service' => $app->serviceType->only(['code', 'name', 'sla_display']),
            'beneficiary' => $app->beneficiary_name,
            'status' => $app->status?->value,
            'status_label' => $app->status?->label(),
            'submitted_at' => $app->submitted_at,
            'sla_due_at' => $app->sla_due_at,
            'completed_at' => $app->completed_at,
            'queue_ticket' => $app->queueTicket?->only(['ticket_number', 'counter', 'status']),
            'logs' => $app->logs->map(fn ($l) => [
                'time' => $l->created_at,
                'action' => $l->action,
                'to_status' => $l->to_status,
                'notes' => $l->notes,
            ]),
            'output' => $app->outputDocument ? [
                'number' => $app->outputDocument->document_number,
                'verify_url' => route('document.verify', ['token' => $app->outputDocument->verification_token]),
                'signed_at' => $app->outputDocument->signed_at,
            ] : null,
        ]);
    }
}
