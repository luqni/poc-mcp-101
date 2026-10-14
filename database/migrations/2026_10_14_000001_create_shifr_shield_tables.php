<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('database_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('db_type')->default('pgsql');
            $table->string('host');
            $table->integer('port')->default(5432);
            $table->string('database');
            $table->string('username');
            $table->text('password');
            $table->boolean('ssl')->default(false);
            $table->string('status')->default('unknown'); // connected, error, unknown
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_source_id')->constrained('database_sources')->cascadeOnDelete();
            $table->json('whitelist_tables')->nullable(); // e.g. ["customers", "orders"]
            $table->json('masked_columns')->nullable();   // e.g. [{"column": "email", "mask_type": "full_redact"}]
            $table->integer('max_query_limit')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_source_id')->constrained('database_sources')->cascadeOnDelete();
            $table->string('name');
            $table->string('key_prefix')->default('shifr_live_');
            $table->string('key_hash')->unique();
            $table->string('masked_key');
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gateway_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_source_id')->nullable()->constrained('database_sources')->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('status'); // success, blocked
            $table->text('query_raw');
            $table->text('query_sanitized')->nullable();
            $table->text('reason')->nullable();
            $table->double('duration_ms', 10, 2)->default(0);
            $table->integer('rows_returned')->default(0);
            $table->string('client_ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_audit_logs');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('security_policies');
        Schema::dropIfExists('database_sources');
    }
};
