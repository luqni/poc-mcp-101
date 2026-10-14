@extends('layouts.app')

@section('header')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Overview</div>
                <h2 class="page-title">Gateway Dashboard</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('databases.index') }}" class="btn btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" />
                            <path d="M4 6v6a8 3 0 0 0 16 0v-6" />
                            <path d="M4 12v6a8 3 0 0 0 16 0v-6" />
                        </svg>
                        Manage Databases
                    </a>
                    <a href="{{ route('api-keys.index') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 5l0 14" />
                            <path d="M5 12l14 0" />
                        </svg>
                        Get MCP Token
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-xl">
    <!-- Metric Cards -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 13a8 8 0 0 1 7 7a6 6 0 0 0 3 -5a9 9 0 0 0 6 -8a3 3 0 0 0 -3 -3a9 9 0 0 0 -8 6a6 6 0 0 0 -5 3" />
                                    <path d="M7 14a6 6 0 0 0 -3 6a6 6 0 0 0 6 -3" />
                                    <path d="M15 9m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium fs-2">{{ number_format($totalQueries) }}</div>
                            <div class="text-secondary">Total Queries Processed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                    <path d="M12 8v4" />
                                    <path d="M12 16h.01" />
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium fs-2 text-danger">{{ number_format($blockedQueries) }}</div>
                            <div class="text-secondary">Blocked Queries (Enforced)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" />
                                    <path d="M4 6v6a8 3 0 0 0 16 0v-6" />
                                    <path d="M4 12v6a8 3 0 0 0 16 0v-6" />
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium fs-2">{{ $activeConnections }} / {{ $totalConnections }}</div>
                            <div class="text-secondary">Active DB Connections</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-azure text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium fs-2">{{ $avgLatency }} ms</div>
                            <div class="text-secondary">Average Query Latency</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Table: 5 Recent Audit Logs -->
    <div class="row row-cards">
        <div class="col-12">
            <x-tabler-card title="Recent Audit Logs (Real-time Stream)" subtitle="Latest queries passing through Shifr Shield MCP Gateway">
                <x-slot:actions>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                        View All Logs
                    </a>
                </x-slot:actions>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Target Database</th>
                                <th>Client / Key</th>
                                <th>Status</th>
                                <th>SQL Query</th>
                                <th>Duration</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLogs as $log)
                                <tr>
                                    <td class="text-secondary text-nowrap">
                                        {{ $log->created_at->format('M d, H:i:s') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-blue-lt">
                                            {{ $log->databaseSource?->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="text-secondary">
                                        {{ $log->apiKey?->name ?? 'Internal / Test' }}
                                    </td>
                                    <td>
                                        @if ($log->status === 'success')
                                            <span class="badge bg-success-lt">
                                                <span class="status-dot status-dot-animated bg-success me-1"></span> Success
                                            </span>
                                        @else
                                            <span class="badge bg-danger-lt">
                                                <span class="status-dot bg-danger me-1"></span> Blocked
                                            </span>
                                        @endif
                                    </td>
                                    <td class="font-monospace text-truncate text-secondary" style="max-width: 320px;">
                                        {{ $log->query_raw }}
                                    </td>
                                    <td class="text-secondary text-nowrap">
                                        {{ $log->duration_ms }} ms
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $log->reason ?? '-' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No queries processed yet. Connect Claude Desktop or Cursor to begin logging!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-tabler-card>
        </div>

        <!-- Registered Database Gateways Summary -->
        <div class="col-12">
            <x-tabler-card title="Registered Database Sources" subtitle="Databases guarded by active security policies">
                <x-slot:actions>
                    <a href="{{ route('databases.index') }}" class="btn btn-sm btn-primary">
                        + Add Connection
                    </a>
                </x-slot:actions>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Database Name</th>
                                <th>Connection Endpoint</th>
                                <th>SSL</th>
                                <th>Status</th>
                                <th>Whitelisted Tables</th>
                                <th>Masked Columns</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($databaseSources as $db)
                                <tr>
                                    <td class="fw-bold">
                                        {{ $db->name }}
                                        <div class="text-secondary small">{{ $db->database }}</div>
                                    </td>
                                    <td class="text-secondary font-monospace">
                                        {{ $db->host }}:{{ $db->port }}
                                    </td>
                                    <td>
                                        @if ($db->ssl)
                                            <span class="badge bg-azure-lt">SSL Enabled</span>
                                        @else
                                            <span class="badge bg-secondary-lt">Standard</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($db->status === 'connected')
                                            <span class="badge bg-success-lt">
                                                <span class="status-dot status-dot-animated bg-success me-1"></span> Connected
                                            </span>
                                        @elseif ($db->status === 'error')
                                            <span class="badge bg-danger-lt">
                                                <span class="status-dot bg-danger me-1"></span> Connection Error
                                            </span>
                                        @else
                                            <span class="badge bg-warning-lt">
                                                <span class="status-dot bg-warning me-1"></span> Untested
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-teal-lt">
                                            {{ count($db->securityPolicy?->whitelist_tables ?? []) }} tables
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-purple-lt">
                                            {{ count($db->securityPolicy?->masked_columns ?? []) }} columns
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="{{ route('policies.edit', $db) }}" class="btn btn-sm btn-outline-secondary">
                                                Policy Rules
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No database sources registered yet. Click "+ Add Connection" to connect PostgreSQL.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-tabler-card>
        </div>
    </div>
</div>
@endsection
