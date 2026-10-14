<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_source_id',
        'api_key_id',
        'status',
        'query_raw',
        'query_sanitized',
        'reason',
        'duration_ms',
        'rows_returned',
        'client_ip',
    ];

    protected $casts = [
        'duration_ms' => 'float',
        'rows_returned' => 'integer',
        'created_at' => 'datetime',
    ];

    public function databaseSource(): BelongsTo
    {
        return $this->belongsTo(DatabaseSource::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
