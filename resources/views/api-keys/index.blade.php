@extends('layouts.app')

@section('header')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Access Control</div>
                <h2 class="page-title">API Keys & MCP Integration</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-key">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14" />
                        <path d="M5 12l14 0" />
                    </svg>
                    Generate New API Key
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-xl" x-data="apiKeyManager()">

    @if (session('new_api_key'))
        <!-- One-Time Secret Key Display Card -->
        <div class="alert alert-warning mb-4" role="alert">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 9v4" />
                        <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                        <path d="M12 16h.01" />
                    </svg>
                </div>
                <div class="w-100">
                    <h4 class="alert-title">Simpan API Key Anda Sekarang!</h4>
                    <p class="text-secondary mb-2">
                        Kunci ini hanya ditampilkan <strong>satu kali saja</strong>. Setelah Anda menutup atau merefresh halaman ini, kunci rahasia tidak akan dapat dilihat kembali demi keamanan.
                    </p>
                    <div class="input-group">
                        <input 
                            type="text" 
                            id="raw-token-input" 
                            class="form-control font-monospace bg-white fw-bold" 
                            readonly 
                            value="{{ session('new_api_key')['raw_token'] }}"
                        >
                        <button class="btn btn-primary" type="button" @click="copyRawKey('{{ session('new_api_key')['raw_token'] }}')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                                <path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" />
                            </svg>
                            <span x-text="copyBtnText">Salin API Key</span>
                        </button>
                    </div>
                    <div class="mt-2 text-secondary small">
                        Kunci ini dihubungkan ke Database: <strong>{{ session('new_api_key')['database'] }}</strong>.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row row-cards">
        <!-- API Keys List Table -->
        <div class="col-12">
            <x-tabler-card title="Active Gateway Tokens" subtitle="Kredensial yang diizinkan untuk membuka koneksi SSE ke Shifr Shield MCP Gateway">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Token Name</th>
                                <th>Database Source</th>
                                <th>Token Masking (Prefix)</th>
                                <th>Status</th>
                                <th>Terakhir Digunakan</th>
                                <th>Total Queries</th>
                                <th>Dibuat Pada</th>
                                <th class="w-1 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($apiKeys as $key)
                                <tr>
                                    <td class="fw-bold">{{ $key->name }}</td>
                                    <td>
                                        <span class="badge bg-blue-lt">
                                            {{ $key->databaseSource?->name ?? 'Unassigned' }}
                                        </span>
                                    </td>
                                    <td>
                                        <code class="text-dark bg-light px-2 py-1 rounded">{{ $key->masked_key }}</code>
                                    </td>
                                    <td>
                                        @if ($key->is_active)
                                            <span class="badge bg-success-lt">
                                                <span class="status-dot status-dot-animated bg-success me-1"></span> Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-lt">
                                                <span class="status-dot bg-secondary me-1"></span> Revoked
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-secondary small">
                                        {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Belum pernah' }}
                                    </td>
                                    <td class="text-secondary">
                                        {{ $key->audit_logs_count }}
                                    </td>
                                    <td class="text-secondary small">
                                        {{ $key->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('api-keys.destroy', $key) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mencabut (revoke) API key ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Revoke
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <p class="mb-2">Belum ada API Key yang dibuat.</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-key">
                                            Buat API Key Pertama
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-tabler-card>
        </div>

        <!-- Setup Instruction Card Tabler -->
        <div class="col-12">
            <x-tabler-card title="Setup Instruction (Claude Desktop & MCP Client)" subtitle="Petunjuk integrasi SSE Server ke claude_desktop_config.json">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">1. Gateway SSE Endpoint URL</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control font-monospace bg-light" readonly value="{{ $mcpGatewaySseUrl }}">
                            <button class="btn btn-outline-secondary" type="button" @click="copyToClipboard('{{ $mcpGatewaySseUrl }}', 'URL Copied!')">
                                Copy URL
                            </button>
                        </div>
                        <small class="text-secondary d-block">
                            Engine Node.js MCP Gateway berjalan pada endpoint ini dan menerima protokol Server-Sent Events (SSE) dengan Bearer Token.
                        </small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">2. Lokasi Konfigurasi Claude Desktop</label>
                        <ul class="text-secondary small ps-3">
                            <li><strong>macOS:</strong> <code>~/Library/Application Support/Claude/claude_desktop_config.json</code></li>
                            <li><strong>Windows:</strong> <code>%APPDATA%\Claude\claude_desktop_config.json</code></li>
                            <li><strong>Linux:</strong> <code>~/.config/Claude/claude_desktop_config.json</code></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0">Snippet Konfigurasi <code>claude_desktop_config.json</code></label>
                        <button class="btn btn-sm btn-outline-primary" @click="copySnippet()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                                <path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" />
                            </svg>
                            <span x-text="snippetCopyText">Salin JSON Snippet</span>
                        </button>
                    </div>

                    <pre class="bg-dark text-white p-3 rounded font-monospace" style="overflow-x: auto;"><code x-text="configSnippet"></code></pre>
                </div>
            </x-tabler-card>
        </div>
    </div>

    <!-- Modal Generate API Key -->
    <div class="modal modal-blur fade" id="modal-create-key" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <form action="{{ route('api-keys.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Generate API Key Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Nama Kunci / Client</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Claude Desktop - Luqni Mac" required>
                            <small class="form-hint">Label untuk melacak siapa yang memanggil MCP gateway.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Pilih Database Target</label>
                            <select name="database_source_id" class="form-select" required>
                                <option value="">-- Pilih Database PostgreSQL --</option>
                                @foreach ($databases as $db)
                                    <option value="{{ $db->id }}">{{ $db->name }} ({{ $db->database }})</option>
                                @endforeach
                            </select>
                            <small class="form-hint">API Key ini akan di-binding langsung ke database terpilih dan tunduk pada aturan policynya.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            Generate Key
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
function apiKeyManager() {
    const sseUrl = '{{ $mcpGatewaySseUrl }}';
    const sampleToken = '{{ session('new_api_key')['raw_token'] ?? 'shifr_live_YOUR_TOKEN_HERE' }}';

    return {
        copyBtnText: 'Salin API Key',
        snippetCopyText: 'Salin JSON Snippet',

        get configSnippet() {
            return JSON.stringify({
                "mcpServers": {
                    "shifr-shield": {
                        "url": sseUrl,
                        "headers": {
                            "Authorization": `Bearer ${sampleToken}`
                        }
                    }
                }
            }, null, 2);
        },

        copyRawKey(text) {
            navigator.clipboard.writeText(text);
            this.copyBtnText = 'Tersalin!';
            setTimeout(() => this.copyBtnText = 'Salin API Key', 2000);
        },

        copySnippet() {
            navigator.clipboard.writeText(this.configSnippet);
            this.snippetCopyText = 'Tersalin ke Clipboard!';
            setTimeout(() => this.snippetCopyText = 'Salin JSON Snippet', 2000);
        },

        copyToClipboard(text, successMsg) {
            navigator.clipboard.writeText(text);
            alert(successMsg);
        }
    };
}
</script>
@endpush
