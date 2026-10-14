@extends('layouts.app')

@section('header')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Security & Compliance</div>
                <h2 class="page-title">Gateway Audit Logs</h2>
                <div class="text-secondary mt-1">
                    Catatan seluruh eksekusi query yang masuk ke MCP Gateway, lengkap dengan inspeksi sanitasi query dan proteksi data.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-xl" x-data="auditLogManager()">
    <!-- Filter Card -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('audit-logs.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Cari Query / Reason</label>
                    <input type="text" name="search" class="form-control" placeholder="SELECT ..., DROP, user..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Database Target</label>
                    <select name="database_id" class="form-select">
                        <option value="">Semua Database</option>
                        @foreach ($databases as $db)
                            <option value="{{ $db->id }}" {{ request('database_id') == $db->id ? 'selected' : '' }}>
                                {{ $db->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success (Dijalankan)</option>
                        <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>Blocked (Dicegah)</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                            <path d="M21 21l-6 -6" />
                        </svg>
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'database_id', 'status']))
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th class="w-1">ID</th>
                        <th>Waktu</th>
                        <th>DB Target</th>
                        <th>Status</th>
                        <th>Query SQL</th>
                        <th>Reason / Penjelasan</th>
                        <th>Durasi (ms)</th>
                        <th class="w-1 text-end">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-secondary small font-monospace">#{{ $log->id }}</td>
                            <td class="text-secondary text-nowrap">
                                <div>{{ $log->created_at->format('Y-m-d') }}</div>
                                <div class="small text-muted">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-blue-lt">
                                    {{ $log->databaseSource?->name ?? 'Deleted Source' }}
                                </span>
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
                            <td class="font-monospace text-truncate text-secondary" style="max-width: 280px;" title="{{ $log->query_raw }}">
                                {{ $log->query_raw }}
                            </td>
                            <td>
                                <span class="small {{ $log->status === 'blocked' ? 'text-danger fw-bold' : 'text-secondary' }}">
                                    {{ $log->reason ?? '-' }}
                                </span>
                            </td>
                            <td class="font-monospace text-secondary text-nowrap">
                                {{ $log->duration_ms }} ms
                            </td>
                            <td class="text-end">
                                <button 
                                    type="button" 
                                    class="btn btn-sm btn-outline-primary"
                                    @click="openDetail({{ json_encode($log) }})"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Tidak ada log query yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-secondary">
                    Menampilkan <span>{{ $logs->firstItem() }}</span> hingga <span>{{ $logs->lastItem() }}</span> dari <span>{{ $logs->total() }}</span> entri
                </p>
                {{ $logs->links('partials.pagination') }}
            </div>
        @endif
    </div>

    <!-- Detail Query Modal (Raw vs Sanitized) -->
    <div class="modal modal-blur fade" id="modal-query-detail" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" x-show="selectedLog">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Audit Log Detail #<span x-text="selectedLog?.id"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Status & Metadata Badges -->
                    <div class="row mb-3 g-2">
                        <div class="col-auto">
                            <span class="badge" :class="selectedLog?.status === 'success' ? 'bg-success-lt' : 'bg-danger-lt'">
                                Status: <strong x-text="selectedLog?.status?.toUpperCase()"></strong>
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-blue-lt">
                                Database: <span x-text="selectedLog?.database_source?.name || 'N/A'"></span>
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-purple-lt">
                                Key: <span x-text="selectedLog?.api_key?.name || 'MCP Client'"></span>
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary-lt">
                                Latency: <span x-text="selectedLog?.duration_ms + ' ms'"></span>
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-teal-lt">
                                Rows: <span x-text="selectedLog?.rows_returned"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Security Evaluation Reason -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Security Evaluation Reason</label>
                        <div class="p-2 rounded bg-light font-monospace small" :class="selectedLog?.status === 'blocked' ? 'text-danger fw-bold' : 'text-dark'" x-text="selectedLog?.reason || 'Executed normally.'"></div>
                    </div>

                    <!-- Query Mentah vs Sanitized Comparison -->
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">1. Query Mentah (Raw Incoming SQL dari LLM):</label>
                                <span class="badge bg-secondary-lt">Original Input</span>
                            </div>
                            <pre class="bg-dark text-white p-3 rounded font-monospace" style="white-space: pre-wrap;" x-text="selectedLog?.query_raw"></pre>
                        </div>

                        <div class="col-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">2. Query Hasil Sanitasi (Rewritten & Limit Injected):</label>
                                <span class="badge bg-green-lt">Guarded Output</span>
                            </div>
                            <template x-if="selectedLog?.query_sanitized">
                                <pre class="bg-dark text-success p-3 rounded font-monospace" style="white-space: pre-wrap;" x-text="selectedLog?.query_sanitized"></pre>
                            </template>
                            <template x-if="!selectedLog?.query_sanitized">
                                <div class="alert alert-danger py-2 mb-0">
                                    <em>Query tidak pernah dieksekusi ke database karena diblokir oleh gateway security policy.</em>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function auditLogManager() {
    return {
        selectedLog: null,
        detailModal: null,

        init() {
            const modalEl = document.getElementById('modal-query-detail');
            if (modalEl && window.bootstrap) {
                this.detailModal = new window.bootstrap.Modal(modalEl);
            }
        },

        openDetail(log) {
            this.selectedLog = log;
            if (!this.detailModal) {
                const modalEl = document.getElementById('modal-query-detail');
                if (modalEl && window.bootstrap) {
                    this.detailModal = new window.bootstrap.Modal(modalEl);
                }
            }
            if (this.detailModal) {
                this.detailModal.show();
            }
        }
    };
}
</script>
@endpush
