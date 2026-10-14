<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\DatabaseSource;
use App\Models\SecurityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $db = DatabaseSource::create([
            'name' => 'Test DB',
            'db_type' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'test_db',
            'username' => 'postgres',
            'password' => 'secret',
            'status' => 'connected',
        ]);

        SecurityPolicy::create([
            'database_source_id' => $db->id,
            'whitelist_tables' => ['users', 'demo_customers'],
            'masked_columns' => [
                ['column' => 'email', 'mask_type' => 'partial_mask']
            ],
            'max_query_limit' => 50,
            'is_active' => true,
        ]);

        ApiKey::create([
            'database_source_id' => $db->id,
            'name' => 'Test Key',
            'key_prefix' => 'shifr_live_',
            'key_hash' => hash('sha256', 'shifr_live_test123'),
            'masked_key' => 'shifr_live_••••••••••••t123',
            'is_active' => true,
        ]);
    }

    public function test_root_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Shifr Shield');
        $response->assertSee('Gateway Dashboard');
    }

    public function test_databases_page_loads_successfully(): void
    {
        $response = $this->get('/databases');
        $response->assertStatus(200);
        $response->assertSee('Database Sources');
    }

    public function test_api_keys_page_loads_successfully(): void
    {
        $response = $this->get('/api-keys');
        $response->assertStatus(200);
        $response->assertSee('API Keys');
    }

    public function test_audit_logs_page_loads_successfully(): void
    {
        $response = $this->get('/audit-logs');
        $response->assertStatus(200);
        $response->assertSee('Gateway Audit Logs');
    }

    public function test_policies_page_loads_successfully(): void
    {
        $db = DatabaseSource::first();
        $response = $this->get("/databases/{$db->id}/policies");
        $response->assertStatus(200);
        $response->assertSee('Data Masking Policy');
    }
}
