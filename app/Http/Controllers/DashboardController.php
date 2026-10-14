<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\DatabaseSource;
use App\Models\GatewayAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalQueries = GatewayAuditLog::count();
        $blockedQueries = GatewayAuditLog::where('status', 'blocked')->count();
        $activeConnections = DatabaseSource::where('status', 'connected')->count();
        $totalConnections = DatabaseSource::count();
        $avgLatency = round(GatewayAuditLog::avg('duration_ms') ?? 0, 2);

        $recentLogs = GatewayAuditLog::with(['databaseSource', 'apiKey'])
            ->latest()
            ->take(5)
            ->get();

        $databaseSources = DatabaseSource::with(['securityPolicy', 'apiKeys'])
            ->withCount('auditLogs')
            ->get();

        $activeApiKeys = ApiKey::where('is_active', true)->count();

        return view('dashboard.index', compact(
            'totalQueries',
            'blockedQueries',
            'activeConnections',
            'totalConnections',
            'avgLatency',
            'recentLogs',
            'databaseSources',
            'activeApiKeys'
        ));
    }
}
