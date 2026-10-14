<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_source_id',
        'name',
        'key_prefix',
        'key_hash',
        'masked_key',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function databaseSource(): BelongsTo
    {
        return $this->belongsTo(DatabaseSource::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(GatewayAuditLog::class);
    }

    /**
     * Generate a new API Key with raw token returned only once.
     */
    public static function generate(string $name, int $databaseSourceId): array
    {
        $prefix = 'shifr_live_';
        $randomPart = Str::random(32);
        $rawToken = $prefix . $randomPart;
        $hash = hash('sha256', $rawToken);
        $last4 = substr($rawToken, -4);
        $maskedKey = $prefix . '••••••••••••' . $last4;

        $model = static::create([
            'database_source_id' => $databaseSourceId,
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => $hash,
            'masked_key' => $maskedKey,
            'is_active' => true,
        ]);

        return [
            'model' => $model,
            'raw_token' => $rawToken,
        ];
    }
}
