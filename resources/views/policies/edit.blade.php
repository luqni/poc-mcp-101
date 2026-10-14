@extends('layouts.app')

@section('header')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('databases.index') }}">Database Sources</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $database->name }}</li>
                    </ol>
                </nav>
                <h2 class="page-title">
                    Security & Data Masking Policy
                </h2>
                <div class="text-secondary mt-1">
                    Aturan keamanan, whitelist tabel, batas limit eksekusi query, dan proteksi kolom sensitif untuk <strong>{{ $database->name }}</strong>.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('databases.index') }}" class="btn btn-outline-secondary">
                    &larr; Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-xl" x-data="policyManager()">
    <form action="{{ route('policies.update', $database) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row row-cards">
            <!-- Left: Table Whitelisting & Limit Configuration -->
            <div class="col-lg-7">
                <x-tabler-card title="1. Whitelist Tables (Akses Terbatas)" subtitle="Hanya tabel-tabel berikut yang diizinkan untuk di-query oleh Claude Desktop / MCP Client.">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="form-label mb-0">Daftar Tabel Tersedia di Database:</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="selectAllTables()">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="deselectAllTables()">Batalkan Pilihan</button>
                            </div>
                        </div>

                        @if (empty($availableTables))
                            <div class="alert alert-warning mb-3">
                                <div>Tidak ada tabel publik yang ditemukan pada database target atau koneksi belum terhubung. Pastikan database aktif dan sudah memiliki tabel.</div>
                            </div>
                        @endif

                        <div class="table-responsive border rounded" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th class="w-1">Izinkan</th>
                                        <th>Nama Tabel</th>
                                        <th>Status Akses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $whitelisted = $policy->whitelist_tables ?? [];
                                        $allTablesList = array_unique(array_merge($availableTables, $whitelisted));
                                    @endphp

                                    @forelse ($allTablesList as $table)
                                        <tr>
                                            <td>
                                                <input 
                                                    type="checkbox" 
                                                    class="form-check-input table-checkbox" 
                                                    name="whitelist_tables[]" 
                                                    value="{{ $table }}" 
                                                    id="table_{{ $table }}"
                                                    {{ in_array($table, $whitelisted) ? 'checked' : '' }}
                                                >
                                            </td>
                                            <td>
                                                <label for="table_{{ $table }}" class="form-check-label font-monospace fw-bold cursor-pointer">
                                                    {{ $table }}
                                                </label>
                                            </td>
                                            <td>
                                                <span class="badge bg-green-lt">Whitelisted Table</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">
                                                Belum ada tabel yang terdeteksi. Anda dapat mengetik manual tabel baru di bawah ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <small class="form-hint mt-2">
                            Setiap query yang mencoba membaca tabel selain yang dicentang akan <strong>ditolak otomatis</strong> oleh MCP Data Gateway dengan status <code>Blocked</code>.
                        </small>
                    </div>

                    <!-- Max Query Limit Input -->
                    <div class="hr-text">Query Execution Boundaries</div>
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Max Query Limit</label>
                            <input 
                                type="number" 
                                name="max_query_limit" 
                                class="form-control" 
                                min="1" 
                                max="1000" 
                                value="{{ old('max_query_limit', $policy->max_query_limit ?? 50) }}" 
                                required
                            >
                            <small class="form-hint">Jika AI Assistant tidak menyertakan <code>LIMIT</code>, gateway akan otomatis menyisipkan batasan ini.</small>
                        </div>
                        <div class="col-md-6 pt-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $policy->is_active ? 'checked' : '' }}>
                                <span class="form-check-label fw-bold">Kebijakan Keamanan Aktif</span>
                            </label>
                            <small class="text-secondary d-block">Nonaktifkan hanya jika dalam mode bypass darurat.</small>
                        </div>
                    </div>
                </x-tabler-card>
            </div>

            <!-- Right: Column Masking Repeater -->
            <div class="col-lg-5">
                <x-tabler-card title="2. Kolom Masking Sensitif (Data Redaction)" subtitle="Masking kolom bernilai sensitif seperti password, email, credit card sebelum data dikirim ke LLM.">
                    <x-slot:actions>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="addRow()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            + Tambah Kolom
                        </button>
                    </x-slot:actions>

                    <!-- Repeater List -->
                    <div class="space-y-2">
                        <template x-for="(item, index) in maskedColumns" :key="index">
                            <div class="card card-sm mb-3 border">
                                <div class="card-body">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-7">
                                            <label class="form-label small mb-1">Nama Kolom</label>
                                            <input 
                                                type="text" 
                                                class="form-control form-control-sm font-monospace" 
                                                placeholder="e.g. email, password, ssn" 
                                                :name="`masked_columns[${index}][column]`"
                                                x-model="item.column"
                                                required
                                            >
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small mb-1">Tipe Masking</label>
                                            <select 
                                                class="form-select form-select-sm" 
                                                :name="`masked_columns[${index}][mask_type]`"
                                                x-model="item.mask_type"
                                            >
                                                <option value="full_redact">Full Redact [REDACTED]</option>
                                                <option value="partial_mask">Partial Mask (u***@***)</option>
                                            </select>
                                        </div>
                                        <div class="col-1 text-end pt-3">
                                            <button type="button" class="btn btn-sm btn-icon btn-ghost-danger" @click="removeRow(index)" title="Hapus Aturan">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="maskedColumns.length === 0">
                            <div class="text-center text-muted py-4 border border-dashed rounded">
                                <p class="mb-2">Belum ada kolom yang disensor.</p>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="addRow()">
                                    + Tambah Kolom Masking
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4">
                        <div class="text-secondary small mb-2">
                            <strong>Cara Kerja Masking:</strong>
                            <ul class="mb-0 ps-3">
                                <li><code>Full Redact</code>: Menggantikan seluruh nilai kolom menjadi string <code>[REDACTED]</code>.</li>
                                <li><code>Partial Mask</code>: Menyisakan karakter awal & akhir (contoh: <code>j***e@example.com</code> atau <code>****-****-****-1234</code>).</li>
                            </ul>
                        </div>
                    </div>
                </x-tabler-card>
            </div>

            <!-- Save Bar Sticky / Submit -->
            <div class="col-12 mt-3">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="text-secondary small">
                            Semua query yang dikirim Claude Desktop via MCP Gateway akan divalidasi seketika berdasarkan aturan ini.
                        </div>
                        <div class="btn-list">
                            <a href="{{ route('databases.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l5 5l10 -10" />
                                </svg>
                                Simpan Kebijakan Keamanan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function policyManager() {
    return {
        maskedColumns: {!! $maskedColumnsJson !!},

        addRow() {
            this.maskedColumns.push({
                column: '',
                mask_type: 'full_redact'
            });
        },

        removeRow(index) {
            this.maskedColumns.splice(index, 1);
        },

        selectAllTables() {
            document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = true);
        },

        deselectAllTables() {
            document.querySelectorAll('.table-checkbox').forEach(cb => cb.checked = false);
        }
    };
}
</script>
@endpush
