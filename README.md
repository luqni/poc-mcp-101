# 🛡️ Shifr Shield — Managed MCP Data Gateway Platform
### *Zero-Trust Data Firewall & Security Gateway for AI Assistants & LLMs*

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Model Context Protocol](https://img.shields.io/badge/MCP-SDK_v1.6-8A2BE2?style=for-the-badge)](https://modelcontextprotocol.io/)
[![Tabler UI](https://img.shields.io/badge/UI-Tabler_Core_100%25-0054a6?style=for-the-badge)](https://tabler.io/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

---

## 📌 Executive Summary & Problem Statement

Menghubungkan AI Assistant (seperti **Claude Desktop, Cursor, Copilot, atau Agen LLM**) langsung ke database operasional perusahaan menimbulkan risiko keamanan dan kepatuhan yang sangat kritis:
1. **Risiko Destruktif (Halusinasi AI):** AI dapat tanpa sengaja menghasilkan query mutasi data (`UPDATE`, `DELETE`, `DROP TABLE`, `TRUNCATE`).
2. **Kebocoran Data Sensitif (PII & Finansial):** LLM menyerap nomor kartu kredit, hash password, atau informasi gaji ke dalam konteks memorinya.
3. **Database Crash (DoS / Memory Exhaustion):** AI menjalankan query `SELECT *` jutaan baris tanpa klausa `LIMIT`.
4. **Blind Spot Kepatuhan:** Tidak adanya jejak audit (*audit trail*) query apa saja yang dieksekusi oleh AI ke database internal.

**Shifr Shield** hadir sebagai **Managed MCP Data Gateway** yang berfungsi sebagai **Firewall Data & Proksi Cerdas**. Shifr Shield berdiri di antara AI Assistant dan database, memastikan setiap query diperiksa, dibatasi, disanitasi, disensor (*data masking*), dan dicatat secara *real-time*.

---

## 🏗️ Architecture Overview

```
               ┌────────────────────────────────────────────────────────┐
               │    Client Layer: Claude Desktop / Cursor / CLI Agent   │
               └───────────────────────────┬────────────────────────────┘
                                           │ MCP Protocol over SSE
                                           ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│              Shifr Shield Data Plane (/mcp-gateway) — Node.js & TypeScript               │
│                                                                                         │
│  - Endpoint: GET /sse & POST /messages                                                   │
│  - Autentikasi: Bearer Token dinamis terhadap tabel api_keys (SHA-256 Hash)            │
│  - Tools Terdaftar: get_schema_metadata, run_readonly_query                              │
│  - Security & Enforcement Engine:                                                       │
│      * Read-Only Enforcer (Memblokir INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE)     │
│      * Whitelist Tables Guard (Hanya tabel diizinkan yang dapat dibaca)                 │
│      * Automatic LIMIT Injection (Menyisipkan batas baris otomatis)                     │
│      * Dynamic Data Masking Engine (Full Redact [REDACTED] & Partial Masking)           │
│      * Direct Audit Logger (Streaming catatan audit langsung ke PostgreSQL)             │
└───────────────────────────┬─────────────────────────────────┬───────────────────────────┘
                            │ Read-Only SQL                    │ Writes Audit Logs
                            ▼                                  ▼
               ┌─────────────────────────┐        ┌─────────────────────────┐
               │ Guarded Database Target │        │   Control Plane DB      │
               │   (Remote/Local PG)     │        │   (PostgreSQL:          │
               │                         │        │    shifr_shield)        │
               └─────────────────────────┘        └───────────▲─────────────┘
                                                              │
                                                  Eloquent ORM│ Management
                                                              │
┌─────────────────────────────────────────────────────────────┴───────────────────────────┐
│          Shifr Shield Web Admin & Control Plane — Laravel 12 + Tabler UI                │
│                                                                                         │
│  - Dashboard (/dashboard): Metrik queries, latency, connection status (.card-sm)        │
│  - Database Sources (/databases): Tambah koneksi & AJAX ping test connection             │
│  - Security Policies (/databases/{id}/policies): Whitelist tabel & repeater masking form│
│  - API Keys (/api-keys): One-time key display & snippet claude_desktop_config.json      │
│  - Audit Logs (/audit-logs): Tabler pagination & inspeksi Query Mentah vs Sanitasi      │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🌟 Fitur Utama

### 1. Control Plane & Web Admin Panel (Laravel 12 + Tabler UI)
- **100% Tabler UI Design System:** Menggunakan murni class HTML bawaan Tabler Core (`.page`, `.page-wrapper`, `.card`, `.card-sm`, `.table-vcenter`, `.badge.bg-success-lt`, `.status-dot-animated`) tanpa inline CSS baru.
- **Dashboard Overview (`/dashboard`):** Menampilkan metrik real-time: Total Queries, Blocked Queries, Active DB Connections, dan Average Latency (ms), serta 5 riwayat query audit terbaru.
- **Database Sources (`/databases`):** Manajemen koneksi PostgreSQL. Dilengkapi tombol **"Test Connection"** interaktif (AJAX ping langsung) yang memperbarui badge status seketika tanpa reload halaman.
- **Security & Policy Rules (`/databases/{id}/policies`):** 
  - **Table Whitelist:** Memilih tabel mana yang diizinkan untuk diakses AI. Query ke tabel lain otomatis diblokir.
  - **Column Data Masking Repeater (Alpine.js):** Konfigurasi masking kolom sensitif:
    - `Full Redact`: Nilai kolom diubah 100% menjadi `[REDACTED]`.
    - `Partial Mask`: Sensor sebagian (contoh: `b***o@enterprise.id` atau `41••••••••4444`).
  - **Max Query Limit:** Batas maksimum hasil baris (default: 50).
- **API Keys & MCP Setup (`/api-keys`):** Generate token gateway dengan format prefix aman (`shifr_live_••••`). Dilengkapi modal *One-Time Display* dan snippet konfigurasi `claude_desktop_config.json` siap salin.
- **Audit Logs (`/audit-logs`):** Jejak audit menyeluruh dengan filter pencarian dan modal detail untuk membandingkan **Query Mentah (Input AI)** vs **Query Hasil Sanitasi (Guarded Output)**.

### 2. Data Plane Engine (Node.js + TypeScript MCP Gateway)
- **SSE Transport Server:** Mendengarkan koneksi Server-Sent Events pada `http://localhost:3000/sse` sesuai spesifikasi `@modelcontextprotocol/sdk`.
- **Bearer Token Authentication:** Memvalidasi token header `Authorization: Bearer shifr_live_***` secara langsung terhadap tabel `api_keys` di PostgreSQL.
- **Exposed MCP Tools:**
  - `get_schema_metadata`: Memberikan daftar tabel dan tipe kolom yang **hanya** diizinkan oleh whitelist policy.
  - `run_readonly_query`: Menjalankan query SQL dengan guardrail ketat.
- **Strict Read-Only Enforcement:** Memblokir seketika kata kunci mutasi (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`, `CREATE`, `REPLACE`, `GRANT`, `REVOKE`, dll.) dan *stacked queries* (pemisah `;`).
- **Automatic Limit Injection:** Jika AI tidak menyertakan `LIMIT`, gateway menyisipkan batasan aman secara otomatis.
- **Zero-Trust Password Decryption:** Password database target didekripsi secara aman di memori menggunakan `APP_KEY` Laravel (AES-256-CBC).

### 3. Natural Language Testing Agent CLI (`npm run ask`)
Memungkinkan pengguna menguji eksekusi query menggunakan **Bahasa Indonesia / bahasa manusia non-teknis** langsung dari terminal tanpa harus meng-install Claude Desktop.

---

## 📁 Struktur Direktori Proyek

```
riset-mcp/
├── app/
│   ├── Http/Controllers/
│   │   ├── ApiKeyController.php          # Manajemen token gateway & snippet Claude
│   │   ├── AuditLogController.php         # Riwayat query & inspeksi sanitasi
│   │   ├── DashboardController.php        # Ringkasan metrik gateway
│   │   ├── DatabaseSourceController.php   # Manajemen koneksi PG & test connection
│   │   └── SecurityPolicyController.php   # Whitelist tabel & aturan masking
│   └── Models/
│       ├── ApiKey.php                     # Model token gateway (SHA-256 hash)
│       ├── DatabaseSource.php             # Model koneksi target (AES-256 password)
│       ├── GatewayAuditLog.php            # Model catatan audit eksekusi
│       └── SecurityPolicy.php             # Model aturan whitelist & masking
├── database/
│   ├── migrations/
│   │   └── 2026_10_14_000001_create_shifr_shield_tables.php
│   └── seeders/
│       └── DatabaseSeeder.php             # Seeder data demo & tabel demo_customers
├── resources/
│   ├── css/app.css                        # Import Tabler Core CSS
│   ├── js/app.js                          # Import Tabler Core JS & Alpine.js
│   └── views/
│       ├── api-keys/index.blade.php       # Halaman API Keys & MCP instruction
│       ├── audit-logs/index.blade.php     # Halaman Audit Logs & detail modal
│       ├── components/
│       │   ├── tabler-badge.blade.php     # Komponen Tabler badge dinamis
│       │   └── tabler-card.blade.php      # Komponen Tabler card standar
│       ├── dashboard/index.blade.php      # Halaman Dashboard utama
│       ├── databases/index.blade.php      # Halaman daftar koneksi database
│       ├── layouts/app.blade.php          # Layout utama Tabler UI
│       ├── partials/pagination.blade.php  # Pagination Blade berbasis Tabler
│       └── policies/edit.blade.php        # Form whitelist tabel & masking repeater
├── routes/
│   └── web.php                            # Rute aplikasi web
├── tests/
│   └── Feature/ExampleTest.php            # Unit & Feature Test suite (100% Passed)
│
└── mcp-gateway/                           # 🛡️ Data Plane Engine (TypeScript)
    ├── src/
    │   ├── crypto.ts                      # SHA-256 hashing & Laravel AES-256 decrypter
    │   ├── db.ts                          # PG connection pooling & audit log recorder
    │   ├── index.ts                       # Express SSE server (/sse & /messages)
    │   ├── mcp-server.ts                  # MCP Tools: get_schema_metadata, run_readonly_query
    │   └── policy.ts                      # Query validator, auto-limit, data masking engine
    ├── ask-cli.ts                         # Natural language query tester (Bahasa Indonesia)
    ├── query-cli.ts                       # Direct SQL query tester
    ├── test-gateway.ts                    # Automated integration test suite
    ├── package.json
    └── tsconfig.json
```

---

## ⚙️ Prasyarat Sistem

- **PHP:** >= 8.2 (ekstensi: `pdo_pgsql`, `openssl`, `mbstring`)
- **Composer:** >= 2.x
- **Node.js:** >= 20.x & **npm** >= 10.x
- **PostgreSQL:** >= 14.x

---

## 🚀 Panduan Instalasi & Setup

### 1. Clone Repository & Konfigurasi Lingkungan
```bash
git clone <url-repo-anda> riset-mcp
cd riset-mcp

# Setup environment Laravel
cp .env.example .env
```

Pastikan konfigurasi PostgreSQL di `.env` sudah sesuai:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=shifr_shield
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password

MCP_GATEWAY_URL=http://localhost:3000
```

### 2. Install Dependensi & Migrasi Database
```bash
# Install paket PHP
composer install

# Generate App Key (kunci enkripsi AES-256)
php artisan key:generate

# Jalankan migrasi dan isi data demo (demo_customers, policy, dan API Key bawaan)
php artisan migrate --seed

# Install asset frontend Tabler & Alpine.js
npm install
npm run build
```

### 3. Setup MCP Gateway (Data Plane)
```bash
cd mcp-gateway
cp .env.example .env
```

Sesuaikan file `mcp-gateway/.env` (pastikan `APP_KEY` sama persis dengan `APP_KEY` pada file root `.env`):
```env
PORT=3000
SHIFR_DB_HOST=127.0.0.1
SHIFR_DB_PORT=5432
SHIFR_DB_NAME=shifr_shield
SHIFR_DB_USER=postgres
SHIFR_DB_PASSWORD=your_postgres_password
APP_KEY=base64:UL/uXWWYcM13SsElMcSamVtGD4RYboJMBhpPGN03I1Q=
```

Install dependensi TypeScript MCP Gateway:
```bash
npm install
npm run build
```

---

## 🏃 Cara Menjalankan Layanan

Buka 2 tab terminal:

### Terminal 1 — Jalankan Web Admin Laravel
```bash
cd riset-mcp
php artisan serve --port=8000
```
Akses di browser: **[http://localhost:8000](http://localhost:8000)**

### Terminal 2 — Jalankan MCP Data Gateway
```bash
cd riset-mcp/mcp-gateway
npm start
```
Gateway akan aktif pada:
- **SSE Transport Endpoint:** `http://localhost:3000/sse`
- **Health Check:** `http://localhost:3000/health`

---

## 🧪 Panduan Pengujian & Demo Fitur

Anda memiliki 4 opsi mudah untuk menguji Shifr Shield:

### Opsi A: Natural Language CLI Tester (Bahasa Indonesia) 🗣️
Anda bisa menguji kemampuan gateway dengan bahasa manusia langsung di terminal:
```bash
cd mcp-gateway

# Query data pelanggan aktif
npm run ask "tampilkan semua pelanggan aktif"

# Menghitung agregasi data
npm run ask "berapa total jumlah pelanggan kita?"

# Menampilkan pelanggan pending
npm run ask "siapa saja pelanggan yang masih pending?"

# Menguji pencegahan mutasi berbahaya
npm run ask "tolong hapus data pelanggan yang suspended"
```

### Opsi B: Direct SQL CLI Tester ⚡
Menjalankan SQL query langsung melalui gateway SSE:
```bash
cd mcp-gateway

# Test query read-only (akan di-masking otomatis)
npm run query "SELECT * FROM demo_customers"

# Test pembatalan mutasi
npm run query "DELETE FROM demo_customers WHERE id = 1"

# Test pembatalan tabel di luar whitelist
npm run query "SELECT * FROM pg_database"
```

### Opsi C: Anthropic MCP Inspector (Browser GUI) 🌐
Uji antarmuka visual MCP tanpa Claude Desktop:
```bash
npx @modelcontextprotocol/inspector
```
1. Masukkan URL: `http://localhost:3000/sse`
2. Masukkan Header: `Authorization: Bearer shifr_live_claudedesktoptestkey2026alpha`
3. Klik **Connect** dan jalankan tool `run_readonly_query`.

### Opsi D: Menghubungkan ke Claude Desktop 🤖
Buka file `claude_desktop_config.json` di komputer Anda:
- **macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows:** `%APPDATA%\Claude\claude_desktop_config.json`
- **Linux:** `~/.config/Claude/claude_desktop_config.json`

Tambahkan blok konfigurasi berikut:
```json
{
  "mcpServers": {
    "shifr-shield": {
      "url": "http://localhost:3000/sse",
      "headers": {
        "Authorization": "Bearer shifr_live_claudedesktoptestkey2026alpha"
      }
    }
  }
}
```
*Restart Claude Desktop. Claude kini memiliki akses aman ke PostgreSQL yang diawasi oleh Shifr Shield.*

---

## 🔒 Security Model & Guardrails

| Guardrail | Aturan Penegakan | Contoh Respon Gateway |
|---|---|---|
| **Query Mutasi** | Memblokir `INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`, dll. | `[SHIFR SHIELD SECURITY BLOCKED]: Blocked: Only read-only queries are permitted` |
| **Stacked Query** | Memblokir query ganda dengan tanda titik koma (`;`). | `[SHIFR SHIELD SECURITY BLOCKED]: Multiple stacked queries are strictly prohibited` |
| **Table Whitelist** | Hanya tabel terdaftar di policy (`demo_customers`, `users`) yang dapat dibaca. | `[SHIFR SHIELD SECURITY BLOCKED]: Table 'pg_database' is not in the whitelist` |
| **Limit Injection** | Otomatis menambahkan atau meng-clamp klausa `LIMIT` ke nilai maksimum policy. | Query `SELECT * FROM tbl` otomatis diubah menjadi `SELECT * FROM tbl LIMIT 25` |
| **Column Masking** | Menyensor data sensitif sebelum dikirim ke AI. | `credit_card` &rarr; `[REDACTED]`<br>`email` &rarr; `b***o@enterprise.id` |
| **Audit Logging** | Mencatat setiap query, durasi (ms), status, dan sanitasi query ke database. | Tercatat di tabel `gateway_audit_logs` & dapat diinspeksi di `/audit-logs` |

---

## 📊 Hasil Pengujian Otomatis (Test Suite)

1. **Laravel Feature & Unit Tests:**
   ```bash
   php artisan test
   ```
   *Output:* **`Tests: 7 passed (14 assertions)`** — 100% pengujian rute dashboard, databases, policies, API keys, dan audit logs lolos verifikasi.

2. **MCP Gateway Automated Integration Tests:**
   ```bash
   cd mcp-gateway && npx tsx test-gateway.ts
   ```
   *Output:* **`🎉 ALL MCP GATEWAY INTEGRATION TESTS PASSED PERFECTLY!`** — Menguji autentikasi 401, metadata skema, masking data, pencegahan mutasi DROP, dan penolakan tabel non-whitelist.

---

## 🗺️ Roadmap Pengembangan (Next Milestones)

- [ ] **Multi-Database Support:** Dukungan konektor MySQL, Microsoft SQL Server, dan Snowflake.
- [ ] **Role-Based Access Control (RBAC):** Kebijakan policy per role pengguna AI.
- [ ] **Live Alerting:** Webhook notifikasi Slack / Discord untuk percobaan mutasi berbahaya.
- [ ] **Redis Caching Layer:** Caching hasil query baca yang identik untuk menghemat resource database.

---

## 📄 Lisensi

Shifr Shield dikembangkan di bawah lisensi [MIT License](LICENSE).
