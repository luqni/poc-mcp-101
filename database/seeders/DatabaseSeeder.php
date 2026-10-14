<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\DatabaseSource;
use App\Models\GatewayAuditLog;
use App\Models\SecurityPolicy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create a demo customers table directly in PostgreSQL for live testing
        DB::statement("
            CREATE TABLE IF NOT EXISTS demo_customers (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                credit_card VARCHAR(64) NOT NULL,
                salary NUMERIC(12, 2) NOT NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Clear existing demo customers and insert fresh rows
        DB::table('demo_customers')->truncate();
        DB::table('demo_customers')->insert([
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@enterprise.id',
                'credit_card' => '4111-2222-3333-4444',
                'salary' => 25000000.00,
                'status' => 'active',
                'created_at' => now()->subDays(10),
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@globaltech.com',
                'credit_card' => '5500-0000-1111-2222',
                'salary' => 32000000.00,
                'status' => 'active',
                'created_at' => now()->subDays(8),
            ],
            [
                'name' => 'Reza Pratama',
                'email' => 'reza.p@fintechindo.co.id',
                'credit_card' => '3782-8224-6310-005',
                'salary' => 18500000.00,
                'status' => 'pending',
                'created_at' => now()->subDays(5),
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@creativecorp.org',
                'credit_card' => '4000-1234-5678-9010',
                'salary' => 28000000.00,
                'status' => 'active',
                'created_at' => now()->subDays(2),
            ],
            [
                'name' => 'Andi Wijaya',
                'email' => 'andi.wijaya@startup.io',
                'credit_card' => '5105-1051-0510-5100',
                'salary' => 15000000.00,
                'status' => 'suspended',
                'created_at' => now()->subDay(),
            ],
        ]);

        // 2. Create Primary Postgres Database Source
        $dbSource = DatabaseSource::create([
            'name' => 'Primary Postgres Cluster',
            'db_type' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'shifr_shield',
            'username' => 'postgres',
            'password' => 'P@ssw0rd',
            'ssl' => false,
            'status' => 'connected',
            'last_tested_at' => now(),
            'last_error' => null,
        ]);

        // 3. Create Security Policy
        $policy = SecurityPolicy::create([
            'database_source_id' => $dbSource->id,
            'whitelist_tables' => ['demo_customers', 'users'],
            'masked_columns' => [
                ['column' => 'credit_card', 'mask_type' => 'full_redact'],
                ['column' => 'email', 'mask_type' => 'partial_mask'],
                ['column' => 'salary', 'mask_type' => 'full_redact'],
            ],
            'max_query_limit' => 25,
            'is_active' => true,
        ]);

        // 4. Create API Keys
        $token1 = 'shifr_live_claudedesktoptestkey2026alpha';
        $apiKey1 = ApiKey::create([
            'database_source_id' => $dbSource->id,
            'name' => 'Claude Desktop - Engineer Workspace',
            'key_prefix' => 'shifr_live_',
            'key_hash' => hash('sha256', $token1),
            'masked_key' => 'shifr_live_••••••••••••lpha',
            'last_used_at' => now()->subMinutes(12),
            'is_active' => true,
        ]);

        $token2 = 'shifr_live_cursoraiintegrationkey2026';
        $apiKey2 = ApiKey::create([
            'database_source_id' => $dbSource->id,
            'name' => 'Cursor AI Assistant',
            'key_prefix' => 'shifr_live_',
            'key_hash' => hash('sha256', $token2),
            'masked_key' => 'shifr_live_••••••••••••2026',
            'last_used_at' => now()->subHours(2),
            'is_active' => true,
        ]);

        // 5. Create realistic Gateway Audit Logs
        GatewayAuditLog::create([
            'database_source_id' => $dbSource->id,
            'api_key_id' => $apiKey1->id,
            'status' => 'success',
            'query_raw' => 'SELECT * FROM demo_customers',
            'query_sanitized' => 'SELECT * FROM demo_customers LIMIT 25',
            'reason' => 'Executed with automatic LIMIT 25 injection and data redaction on 3 columns (credit_card, email, salary).',
            'duration_ms' => 14.80,
            'rows_returned' => 5,
            'client_ip' => '127.0.0.1',
            'created_at' => now()->subMinutes(12),
        ]);

        GatewayAuditLog::create([
            'database_source_id' => $dbSource->id,
            'api_key_id' => $apiKey1->id,
            'status' => 'blocked',
            'query_raw' => 'DROP TABLE users;',
            'query_sanitized' => null,
            'reason' => "Blocked: Mutation keyword 'DROP' forbidden in read-only MCP data gateway.",
            'duration_ms' => 0.85,
            'rows_returned' => 0,
            'client_ip' => '127.0.0.1',
            'created_at' => now()->subMinutes(25),
        ]);

        GatewayAuditLog::create([
            'database_source_id' => $dbSource->id,
            'api_key_id' => $apiKey2->id,
            'status' => 'blocked',
            'query_raw' => 'SELECT * FROM pg_catalog.pg_shadow;',
            'query_sanitized' => null,
            'reason' => "Blocked: Table 'pg_shadow' is not in the whitelist for this database policy.",
            'duration_ms' => 1.10,
            'rows_returned' => 0,
            'client_ip' => '127.0.0.1',
            'created_at' => now()->subHours(1),
        ]);

        GatewayAuditLog::create([
            'database_source_id' => $dbSource->id,
            'api_key_id' => $apiKey1->id,
            'status' => 'blocked',
            'query_raw' => "DELETE FROM demo_customers WHERE status = 'suspended';",
            'query_sanitized' => null,
            'reason' => "Blocked: Mutation keyword 'DELETE' forbidden in read-only MCP data gateway.",
            'duration_ms' => 0.92,
            'rows_returned' => 0,
            'client_ip' => '127.0.0.1',
            'created_at' => now()->subHours(3),
        ]);

        GatewayAuditLog::create([
            'database_source_id' => $dbSource->id,
            'api_key_id' => $apiKey2->id,
            'status' => 'success',
            'query_raw' => "SELECT name, email, status FROM demo_customers WHERE status = 'active' ORDER BY id ASC",
            'query_sanitized' => "SELECT name, email, status FROM demo_customers WHERE status = 'active' ORDER BY id ASC LIMIT 25",
            'reason' => 'Executed with partial masking applied on column email.',
            'duration_ms' => 12.35,
            'rows_returned' => 3,
            'client_ip' => '127.0.0.1',
            'created_at' => now()->subHours(4),
        ]);
    }
}
