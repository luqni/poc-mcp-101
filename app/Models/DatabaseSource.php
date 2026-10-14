<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PDO;
use Throwable;

class DatabaseSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'db_type',
        'host',
        'port',
        'database',
        'username',
        'password',
        'ssl',
        'status',
        'last_tested_at',
        'last_error',
    ];

    protected $casts = [
        'port' => 'integer',
        'ssl' => 'boolean',
        'last_tested_at' => 'datetime',
        'password' => 'encrypted',
    ];

    public function securityPolicy(): HasOne
    {
        return $this->hasOne(SecurityPolicy::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(GatewayAuditLog::class);
    }

    /**
     * Test the connection to the remote or local database source.
     */
    public function testConnection(): array
    {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            if ($this->ssl) {
                $dsn .= ";sslmode=require";
            } else {
                $dsn .= ";sslmode=prefer";
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ];

            $pdo = new PDO($dsn, $this->username, $this->password, $options);
            $stmt = $pdo->query("SELECT 1");
            $stmt->fetch();

            $this->update([
                'status' => 'connected',
                'last_tested_at' => now(),
                'last_error' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Connection established successfully.',
            ];
        } catch (Throwable $e) {
            $this->update([
                'status' => 'error',
                'last_tested_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch list of public tables from the target database.
     */
    public function fetchTables(): array
    {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            if ($this->ssl) {
                $dsn .= ";sslmode=require";
            } else {
                $dsn .= ";sslmode=prefer";
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ];

            $pdo = new PDO($dsn, $this->username, $this->password, $options);
            $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name;";
            $stmt = $pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch list of columns for public tables.
     */
    public function fetchColumns(): array
    {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            if ($this->ssl) {
                $dsn .= ";sslmode=require";
            } else {
                $dsn .= ";sslmode=prefer";
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ];

            $pdo = new PDO($dsn, $this->username, $this->password, $options);
            $query = "SELECT table_name, column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' ORDER BY table_name, ordinal_position;";
            $stmt = $pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
