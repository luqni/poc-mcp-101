<?php

namespace App\Http\Controllers;

use App\Models\DatabaseSource;
use App\Models\GatewayAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = GatewayAuditLog::with(['databaseSource', 'apiKey'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('database_id')) {
            $query->where('database_source_id', $request->database_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('query_raw', 'ilike', "%{$search}%")
                  ->orWhere('reason', 'ilike', "%{$search}%");
            });
        }

        $logs = $query->paginate(15)->withQueryString();
        $databases = DatabaseSource::orderBy('name')->get();

        return view('audit-logs.index', compact('logs', 'databases'));
    }

    public function show(GatewayAuditLog $log): JsonResponse
    {
        $log->load(['databaseSource', 'apiKey']);

        return response()->json([
            'id' => $log->id,
            'time' => $log->created_at->format('Y-m-d H:i:s'),
            'database' => $log->databaseSource?->name ?? 'Unknown',
            'api_key' => $log->apiKey?->name ?? 'Direct',
            'status' => $log->status,
            'reason' => $log->reason,
            'duration_ms' => $log->duration_ms,
            'rows_returned' => $log->rows_returned,
            'query_raw' => $log->query_raw,
            'query_sanitized' => $log->query_sanitized,
            'client_ip' => $log->client_ip,
        ]);
    }
}
