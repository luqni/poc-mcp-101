@extends('layouts.app')

@section('header')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Connections</div>
                <h2 class="page-title">Database Sources</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-database">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14" />
                        <path d="M5 12l14 0" />
                    </svg>
                    Tambah Koneksi
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-xl" x-data="databaseManager()">
    <div class="row row-cards">
        <div class="col-12">
            <x-tabler-card title="Registered Databases" subtitle="PostgreSQL sources connected and protected by Shifr Shield Gateway">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Name & DB</th>
                                <th>Host & Port</th>
                                <th>Username</th>
                                <th>SSL</th>
                                <th>Status</th>
                                <th>Last Ping</th>
                                <th>API Keys</th>
                                <th class="w-1 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($databases as $db)
                                <tr id="db-row-{{ $db->id }}">
                                    <td>
                                        <div class="fw-bold">{{ $db->name }}</div>
                                        <div class="text-secondary small font-monospace">{{ $db->database }}</div>
                                    </td>
                                    <td class="font-monospace text-secondary">
                                        {{ $db->host }}:{{ $db->port }}
                                    </td>
                                    <td class="text-secondary">
                                        {{ $db->username }}
                                    </td>
                                    <td>
                                        @if ($db->ssl)
                                            <span class="badge bg-azure-lt">SSL</span>
                                        @else
                                            <span class="badge bg-secondary-lt">Off</span>
                                        @endif
                                    </td>
                                    <td>
                                        <template x-if="statusMap[{{ $db->id }}]">
                                            <span>
                                                <template x-if="statusMap[{{ $db->id }}].status === 'testing'">
                                                    <span class="badge bg-warning-lt">
                                                        <span class="status-dot status-dot-animated bg-warning me-1"></span> Testing...
                                                    </span>
                                                </template>
                                                <template x-if="statusMap[{{ $db->id }}].status === 'connected'">
                                                    <span class="badge bg-success-lt">
                                                        <span class="status-dot status-dot-animated bg-success me-1"></span> Connected
                                                    </span>
                                                </template>
                                                <template x-if="statusMap[{{ $db->id }}].status === 'error'">
                                                    <span class="badge bg-danger-lt" :title="statusMap[{{ $db->id }}].message">
                                                        <span class="status-dot bg-danger me-1"></span> Connection Error
                                                    </span>
                                                </template>
                                            </span>
                                        </template>

                                        <template x-if="!statusMap[{{ $db->id }}]">
                                            @if ($db->status === 'connected')
                                                <span class="badge bg-success-lt">
                                                    <span class="status-dot status-dot-animated bg-success me-1"></span> Connected
                                                </span>
                                            @elseif ($db->status === 'error')
                                                <span class="badge bg-danger-lt" title="{{ $db->last_error }}">
                                                    <span class="status-dot bg-danger me-1"></span> Error
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-lt">
                                                    <span class="status-dot bg-secondary me-1"></span> Untested
                                                </span>
                                            @endif
                                        </template>
                                    </td>
                                    <td class="text-secondary small">
                                        <span x-text="statusMap[{{ $db->id }}] ? statusMap[{{ $db->id }}].last_tested_at : '{{ $db->last_tested_at ? $db->last_tested_at->diffForHumans() : 'Never' }}'">
                                            {{ $db->last_tested_at ? $db->last_tested_at->diffForHumans() : 'Never' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-blue-lt">
                                            {{ $db->api_keys_count }} active
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-list flex-nowrap justify-content-end">
                                            <!-- Test Connection Button -->
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-outline-secondary"
                                                @click="testConnection({{ $db->id }}, '{{ route('databases.test', $db) }}')"
                                                :disabled="statusMap[{{ $db->id }}]?.status === 'testing'"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M7 10v4h4" />
                                                    <path d="M17 14v-4h-4" />
                                                    <path d="M21 12a9 9 0 1 0 -9 9" />
                                                </svg>
                                                Test Connection
                                            </button>

                                            <!-- Policy Rules Button -->
                                            <a href="{{ route('policies.edit', $db) }}" class="btn btn-sm btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                                    <path d="M12 11m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                    <path d="M12 12l0 2.5" />
                                                </svg>
                                                Policies
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('databases.destroy', $db) }}" method="POST" onsubmit="return confirm('Hapus koneksi database ini? Semua audit logs dan key terkait akan ikut terhapus.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M4 7l16 0" />
                                                        <path d="M10 11l0 6" />
                                                        <path d="M14 11l0 6" />
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <p class="mb-2">Belum ada database PostgreSQL yang didaftarkan.</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-database">
                                            + Daftarkan Database Sekarang
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-tabler-card>
        </div>
    </div>

    <!-- Alert Banner when AJAX test connection finishes -->
    <template x-if="toastMessage">
        <div class="alert mt-3 alert-dismissible" :class="toastType === 'success' ? 'alert-success' : 'alert-danger'" role="alert">
            <div class="d-flex">
                <div x-text="toastMessage"></div>
            </div>
            <a class="btn-close" @click="toastMessage = null" aria-label="close"></a>
        </div>
    </template>

    <!-- Modal Tambah Koneksi -->
    <div class="modal modal-blur fade" id="modal-add-database" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('databases.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Koneksi Database PostgreSQL</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label required">Display Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Analytics Data Warehouse" required value="{{ old('name') }}">
                                <small class="form-hint">Nama deskriptif untuk mengidentifikasi database ini di Shifr Shield.</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Database Engine</label>
                                <select class="form-select" disabled>
                                    <option selected>PostgreSQL</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label required">Host / IP Address</label>
                                <input type="text" name="host" class="form-control font-monospace" placeholder="127.0.0.1 atau db.internal.net" required value="{{ old('host', '127.0.0.1') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Port</label>
                                <input type="number" name="port" class="form-control font-monospace" placeholder="5432" required value="{{ old('port', '5432') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Database Name</label>
                                <input type="text" name="database" class="form-control font-monospace" placeholder="shifr_shield" required value="{{ old('database') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Username</label>
                                <input type="text" name="username" class="form-control font-monospace" placeholder="postgres" required value="{{ old('username', 'postgres') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Password</label>
                            <input type="password" name="password" class="form-control font-monospace" placeholder="••••••••" required>
                            <small class="form-hint">Password akan dienkripsi secara aman dengan algoritma AES-256-CBC Laravel.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ssl" value="1" {{ old('ssl') ? 'checked' : '' }}>
                                <span class="form-check-label">Aktifkan Enkripsi SSL (Require SSL Mode)</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l5 5l10 -10" />
                            </svg>
                            Simpan & Uji Koneksi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function databaseManager() {
    return {
        statusMap: {},
        toastMessage: null,
        toastType: 'success',
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        async testConnection(dbId, testUrl) {
            this.statusMap[dbId] = {
                status: 'testing',
                message: 'Testing connection...',
                last_tested_at: 'Pinging...'
            };

            try {
                const response = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.statusMap[dbId] = {
                        status: 'connected',
                        message: data.message,
                        last_tested_at: data.last_tested_at
                    };
                    this.toastType = 'success';
                    this.toastMessage = `Connection Test Succeeded: ${data.message}`;
                } else {
                    this.statusMap[dbId] = {
                        status: 'error',
                        message: data.message,
                        last_tested_at: data.last_tested_at
                    };
                    this.toastType = 'danger';
                    this.toastMessage = `Connection Test Failed: ${data.message}`;
                }
            } catch (err) {
                this.statusMap[dbId] = {
                    status: 'error',
                    message: err.message,
                    last_tested_at: 'Error'
                };
                this.toastType = 'danger';
                this.toastMessage = `Connection error: ${err.message}`;
            }
        }
    };
}
</script>
@endpush
